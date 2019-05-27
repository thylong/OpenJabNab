#include <QByteArray>
#include <memory>
#include "apimanager.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "httphandler.h"
#include "nabaztagmanager.h"
#include "httprequest.h"
#include "log.h"
//#include "netdump.h"
#include "QsLog.h"
//#include "openjabnab.h"
#include "pluginmanager.h"
#include "settings.h"

HttpHandler::HttpHandler(QTcpSocket * s, bool api, bool violetapi):pluginManager(PluginManager::Instance())
{
	incomingHttpSocket = s;
	httpApi = api;
	httpVioletApi = violetapi;
	bytesToReceive = 0;
	connect(s, SIGNAL(readyRead()), this, SLOT(ReceiveData()));
}

HttpHandler::~HttpHandler() {}

void HttpHandler::ReceiveData()
{
	receivedData += incomingHttpSocket->readAll();
	if(bytesToReceive == 0 && (receivedData.size() >= 4))
		bytesToReceive = *(int *)receivedData.left(4).constData();

	if(bytesToReceive != 0 && (receivedData.size() == bytesToReceive))
		HandleBunnyHTTPRequest();
}

void HttpHandler::HandleBunnyHTTPRequest()
{
	HTTPRequest request(receivedData);
	QString uri = request.GetURI();
	QRegExp rx("(ojn|vl)/([A-Z]{2})/api");
	if (uri.startsWith("/ojn_api/"))
	{
		QsLogging::Logger::DumpLog(request.GetRawURI(), "Api Call");
		if(httpApi)
		{
			std::unique_ptr<ApiManager::ApiAnswer> apianswer(ApiManager::Instance().ProcessApiCall(uri.mid(9), request));
			//QByteArray answer = "Content-Type: text/xml\n\n" + apianswer->GetData();
			QByteArray answer = apianswer->GetData();
			incomingHttpSocket->write(answer);
			QsLogging::Logger::DumpLog(answer, "Api Answer");
		}
		else
			incomingHttpSocket->write("Api is disabled");
	}
	else if(uri.contains(rx) || uri.contains("/ojn/FR/api") || uri.startsWith("/vl/FR/api"))
	//else if (uri.startsWith("/ojn/FR/api") || uri.startsWith("/vl/FR/api"))
	{
		QsLogging::Logger::DumpLog(request.GetRawURI(), "Violet Api Call");
		if(httpVioletApi)
		{
			std::unique_ptr<ApiManager::ApiAnswer> apianswer(ApiManager::Instance().ProcessApiCall(uri, request));
			//QByteArray answer = "Content-Type: text/xml\n\n" + apianswer->GetData();
			QByteArray answer = apianswer->GetData();
			incomingHttpSocket->write(answer);
			QsLogging::Logger::DumpLog(answer, "Violet Api Answer");
		}
		else
			incomingHttpSocket->write("Violet Api is disabled");
	}
	else if (uri.startsWith("/vl/FR/p3.jsp"))
	{
		NabaztagManager::handlePing(request, incomingHttpSocket);
	}
	else
	{
		QsLogging::Logger::DumpLog(request.GetRawURI(), "HTTP Request");
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
			QsLogging::Logger::DumpLog(request.reply, "HTTP Answer");
	}
	Disconnect();
}

void HttpHandler::Disconnect()
{
	incomingHttpSocket->disconnectFromHost();
	//incomingHttpSocket->abort();
	// Delete incomingHttpSocket when it will be disconnected
	connect(incomingHttpSocket, SIGNAL(disconnected()), incomingHttpSocket, SLOT(deleteLater()));
	deleteLater();
}
