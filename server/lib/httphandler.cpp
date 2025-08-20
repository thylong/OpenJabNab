#include <memory>
#include <QByteArray>
#include <QTcpSocket>
#include <QHostAddress>

#include "apimanager.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "httphandler.h"
#include "nabaztagmanager.h"
#include "httprequest.h"
#include "log.h"
#include "pluginmanager.h"
#include "settings.h"

#define DEFAULT_HTTP_TIMEOUT_S	10

HttpHandler::HttpHandler(QTcpSocket * s, bool api, bool violetapi)
	: pluginManager(PluginManager::Instance())
	, incomingHttpSocket(s)
	, httpApi(api)
	, httpVioletApi(violetapi)
	, bytesToReceive(0)
	, _lastMsgTime(std::chrono::system_clock::now())
{
	LogDebug(QString("HttpHandler::HttpHandler() - New HTTP connection from %1:%2").arg(s->peerAddress().toString()).arg(s->peerPort()));
	QObject::connect(s, &QTcpSocket::readyRead, this, &HttpHandler::ReceiveData);
}

HttpHandler::~HttpHandler() 
{
	//LogDebug(QString("Delete HTTPHandler 0x%1").arg((quintptr)this, QT_POINTER_SIZE * 2, 16, QChar('0')));
}

bool HttpHandler::shouldDelete(void)
{
	if(!incomingHttpSocket)
		return true;
	auto now = std::chrono::system_clock::now();
	auto dt = std::chrono::duration_cast<std::chrono::seconds>(now - _lastMsgTime).count();
	auto maxDt = GlobalSettings::GetInt("Timeout/Http",DEFAULT_HTTP_TIMEOUT_S);
	return dt > maxDt;
}

void HttpHandler::cleanup(void)
{
	if(incomingHttpSocket)
	{
		incomingHttpSocket->disconnectFromHost();
		incomingHttpSocket = nullptr;
		return;
	}
	
	// Delete incomingHttpSocket when it will be disconnected
	//QObject::connect(incomingHttpSocket, &QTcpSocket::disconnected, incomingHttpSocket, &QObject::deleteLater);
	deleteLater();
}

void HttpHandler::ReceiveData()
{
	_lastMsgTime = std::chrono::system_clock::now();
	QByteArray newData = incomingHttpSocket->readAll();
	LogDebug(QString("HttpHandler::ReceiveData() - Received %1 bytes: %2").arg(newData.size()).arg(QString(newData.left(100))));
	receivedData += newData;
	
	// Check if this looks like standard HTTP (starts with "GET ", "POST ", etc.)
	if(bytesToReceive == 0 && receivedData.size() >= 4)
	{
		if(receivedData.startsWith("GET ") || receivedData.startsWith("POST ") || receivedData.startsWith("PUT ") || receivedData.startsWith("HEAD "))
		{
			LogDebug("HttpHandler::ReceiveData() - Detected standard HTTP request, checking if complete");
			// Standard HTTP - check if we have complete request (ends with \r\n\r\n)
			if(receivedData.contains("\r\n\r\n"))
			{
				LogDebug("HttpHandler::ReceiveData() - Complete HTTP request received, converting to binary format");
				// Convert standard HTTP to binary format expected by HandleHTTPRequest
				QByteArray convertedData = ConvertHttpToBinary(receivedData);
				if(!convertedData.isEmpty())
				{
					receivedData = convertedData;
					HandleHTTPRequest();
				}
			}
			return;
		}
		else
		{
			// Binary protocol - original logic
			bytesToReceive = *(int *)receivedData.left(4).constData();
			LogDebug(QString("HttpHandler::ReceiveData() - Parsed binary length header: %1 bytes expected").arg(bytesToReceive));
		}
	}

	LogDebug(QString("HttpHandler::ReceiveData() - Total received: %1, Expected: %2").arg(receivedData.size()).arg(bytesToReceive));
	
	if(bytesToReceive != 0 && (receivedData.size() == bytesToReceive))
	{
		LogDebug("HttpHandler::ReceiveData() - Complete binary message received, calling HandleHTTPRequest()");
		HandleHTTPRequest();
	}
}

void HttpHandler::HandleHTTPRequest()
{
	HTTPRequest request(receivedData);
	QString uri = request.GetURI();
	LogDebug(QString("HttpHandler::HandleHTTPRequest() - Processing URI: '%1'").arg(uri));
	QRegExp rx("(ojn|vl)/([A-Z]{2})/api");
	if (uri.startsWith("/ojn_api/"))
	{
		LogDump(request.GetRawURI(), "Api Call");
		LogDebug(QString("HttpHandler::HandleHTTPRequest() - API call detected, URI: %1").arg(uri));
		if(httpApi)
		{
			std::unique_ptr<ApiAnswers::Answer> apianswer(ApiManager::Instance().ProcessApiCall(uri.mid(9), request));
			if(apianswer)
			{
				QByteArray answer = "HTTP/1.1 200 OK\r\nContent-Type: text/xml\r\nConnection: close\r\n\r\n" + apianswer->GetData();
				LogDebug(QString("HttpHandler::HandleHTTPRequest() - Sending API response with headers, total size: %1").arg(answer.size()));
				LogDebug(QString("HttpHandler::HandleHTTPRequest() - Response headers: %1").arg(QString(answer.left(100))));
				incomingHttpSocket->write(answer);
				LogDump(answer, "Api Answer");
			}
		}
		else
			incomingHttpSocket->write("HTTP/1.1 503 Service Unavailable\r\nContent-Type: text/plain\r\nConnection: close\r\n\r\nApi is disabled");
	}
	else if(uri.contains(rx) || uri.contains("/ojn/FR/api") || uri.startsWith("/vl/FR/api"))
	//else if (uri.startsWith("/ojn/FR/api") || uri.startsWith("/vl/FR/api"))
	{
		LogDump(request.GetRawURI(), "Violet Api Call");
		if(httpVioletApi)
		{
			std::unique_ptr<ApiAnswers::Answer> apianswer(ApiManager::Instance().ProcessApiCall(uri, request));
			if(apianswer)
			{
				QByteArray answer = "HTTP/1.1 200 OK\r\nContent-Type: text/xml\r\nConnection: close\r\n\r\n" + apianswer->GetData();
				LogDebug(QString("HttpHandler::HandleHTTPRequest() - Sending Violet API response with headers, total size: %1").arg(answer.size()));
				LogDebug(QString("HttpHandler::HandleHTTPRequest() - Violet response headers: %1").arg(QString(answer.left(100))));
				if(answer.size() && incomingHttpSocket)
				{
					incomingHttpSocket->write(answer);
					LogDump(answer, "Violet Api Answer");
				}
			}
		}
		else
			incomingHttpSocket->write("HTTP/1.1 503 Service Unavailable\r\nContent-Type: text/plain\r\nConnection: close\r\n\r\nViolet Api is disabled");
	}
	else if (uri.startsWith("/vl/FR/p3.jsp"))
	{
		NabaztagManager::Instance().handlePing(request, incomingHttpSocket);
	}
	else
	{
		LogDump(request.GetRawURI(), "HTTP Request");
		pluginManager.HttpRequestBefore(request);
		if (!pluginManager.HttpRequestHandle(request))
		{
			if(uri.contains("rfid.jsp"))
			{
				LogError(QString("No action associated to RFID tag %1 for bunny %2").arg(request.GetArg("t"), request.GetArg("sn")));
				request.reply = "404 Not Found";
			}
			else
			{
				LogError(QString("Unable to handle HTTP Request : ") + request.toString());
				request.reply = "404 Not Found";
			}
		}
		pluginManager.HttpRequestAfter(request);
		incomingHttpSocket->write(request.reply);
		if(!uri.contains("itmode.jsp") && !uri.contains(".mp3") && !uri.contains(".chor") && !uri.contains("bc.jsp") && request.reply.size() < 256) // Don't dump too big answers
			LogDump(request.reply, "HTTP Answer");
	}
	cleanup();
}

QByteArray HttpHandler::ConvertHttpToBinary(const QByteArray& httpData)
{
	LogDebug("HttpHandler::ConvertHttpToBinary() - Converting standard HTTP to binary format");
	
	// Parse HTTP request line: "GET /path HTTP/1.1"
	QString httpString = QString::fromUtf8(httpData);
	QStringList lines = httpString.split("\r\n");
	if(lines.isEmpty())
		return QByteArray();
		
	QStringList requestLine = lines[0].split(" ");
	if(requestLine.size() < 2)
		return QByteArray();
		
	QString method = requestLine[0];
	QString uri = requestLine[1];
	
	// Build headers string (skip the request line)
	QStringList headerLines;
	for(int i = 1; i < lines.size() && !lines[i].isEmpty(); i++)
	{
		headerLines << lines[i];
	}
	QString headers = headerLines.join("\r\n");
	
	// Create binary format:
	// [4-byte length][1-byte request type][headers\0][URI\0]
	QByteArray binaryData;
	
	// Determine request type
	quint8 requestType = 1; // GET
	if(method == "POST") requestType = 2;
	else if(method == "POSTRAW") requestType = 3;
	
	// Build binary payload
	QByteArray payload;
	payload.append(requestType);
	payload.append(headers.toUtf8());
	payload.append('\0');
	payload.append(uri.toUtf8());
	payload.append('\0');
	
	// Add 4-byte length header
	quint32 totalLength = payload.size() + 4;
	binaryData.append((char*)&totalLength, 4);
	binaryData.append(payload);
	
	LogDebug(QString("HttpHandler::ConvertHttpToBinary() - Converted to %1 bytes binary format").arg(binaryData.size()));
	return binaryData;
}
