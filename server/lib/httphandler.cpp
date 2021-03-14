#include <memory>
#include <QByteArray>
#include <QTcpSocket>

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
	receivedData += incomingHttpSocket->readAll();
	if(bytesToReceive == 0 && (receivedData.size() >= 4))
		bytesToReceive = *(int *)receivedData.left(4).constData();

	if(bytesToReceive != 0 && (receivedData.size() == bytesToReceive))
		HandleHTTPRequest();
}

void HttpHandler::HandleHTTPRequest()
{
	HTTPRequest request(receivedData);
	QString uri = request.GetURI();
	QRegExp rx("(ojn|vl)/([A-Z]{2})/api");
	if (uri.startsWith("/ojn_api/"))
	{
		LogDump(request.GetRawURI(), "Api Call");
		if(httpApi)
		{
			std::unique_ptr<ApiAnswers::Answer> apianswer(ApiManager::Instance().ProcessApiCall(uri.mid(9), request));
			if(apianswer)
			{
				//QByteArray answer = "Content-Type: text/xml\n\n" + apianswer->GetData();
				QByteArray answer = apianswer->GetData();
				incomingHttpSocket->write(answer);
				LogDump(answer, "Api Answer");
			}
		}
		else
			incomingHttpSocket->write("Api is disabled");
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
				//QByteArray answer = "Content-Type: text/xml\n\n" + apianswer->GetData();
				QByteArray answer = apianswer->GetData();
				incomingHttpSocket->write(answer);
				LogDump(answer, "Violet Api Answer");
			}
		}
		else
			incomingHttpSocket->write("Violet Api is disabled");
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
				LogError(QString("No action associated to RFID tag %1 for bunny %2").arg(request.GetArg("sn"), request.GetArg("t")));
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
