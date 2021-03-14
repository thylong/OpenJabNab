#ifndef _HTTPHANDLER_H_
#define _HTTPHANDLER_H_

#include <QObject>
#include <chrono>
#include "global.h"

class QTcpSocket;

class PluginManager;
class ApiManager;
class VioletApiManager;

class OJN_EXPORT HttpHandler 
	: public QObject
{
	Q_OBJECT

public:
	HttpHandler(QTcpSocket *, bool, bool);
	virtual ~HttpHandler();

	bool shouldDelete(void);

public slots:
	void Disconnect();

private slots:
	void ReceiveData();

private:
	void HandleBunnyHTTPRequest();

	PluginManager & pluginManager;
	QTcpSocket * incomingHttpSocket;
	bool httpApi;
	bool httpVioletApi;
	QByteArray receivedData;
	int bytesToReceive;
	std::chrono::time_point<std::chrono::system_clock> _lastMsgTime;
};

#endif
