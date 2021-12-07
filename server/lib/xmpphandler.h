#ifndef _XMPPHANDLER_H_
#define _XMPPHANDLER_H_
#include <chrono>

#include <QByteArray>
#include <QList>
#include <QObject>
#include <QTimer>
#include <QTcpSocket>

#include "global.h"

class Bunny;
class PluginManager;
class OJN_EXPORT XmppHandler : public QObject
{
	Q_OBJECT

public:
	XmppHandler(QTcpSocket *);
	void WriteDataToBunny(QByteArray const& p);
	void WriteExpertDataToBunny(QByteArray const& p);
	void WriteToBunnyAndLog(QByteArray const&);
	QByteArray const& GetXmppDomain() { return OjnXmppDomain; }
	unsigned int currentAuthStep;
	QString GetBunnyIp();
	QString bunny_real_ip;

	bool shouldDelete(void);

public slots:
	void cleanup();
	//void Timeout();
	//void Bind();

protected:
	virtual ~XmppHandler();

private slots:
	void HandleBunnyXmppMessage();

private:
	QList<QByteArray> XmlParse(QByteArray const&);
	void WriteToBunny(QByteArray const&);

	QTcpSocket * incomingXmppSocket;
	PluginManager & pluginManager;
	Bunny * bunny;
	QByteArray msgQueue;

	QByteArray OjnXmppDomain;

	static unsigned short msgNb;
	static unsigned short msgStreamNb;

	QString tempMessage;
	QByteArray bindingResource;
	QByteArray lastQueryResource;

	int streamingQueryCount;
	unsigned long long tempInXmppTraffic;
	unsigned long long tempOutXmppTraffic;
	std::chrono::time_point<std::chrono::system_clock> _lastBindTime,
																										 _lastMsgTime;
};

#endif
