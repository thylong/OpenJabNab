#ifndef _OPENJABNAB_H_
#define _OPENJABNAB_H_

#include <QCoreApplication>
#include <QTcpServer>
#include "apimanager.h"
#include "pluginmanager.h"

class OpenJabNab : public QCoreApplication
{
	Q_OBJECT

public:
	OpenJabNab(int argc, char ** argv);
	void Close();
	virtual ~OpenJabNab();

signals:
	void Quit();

private slots:
//	void RotateLog();
	void NabaztagStatus();
	void SaveAccounts();
	void NewHTTPConnection();
	void NewXMPPConnection();
//	void SendStatsToSense();

private:
	void insertServerInDb();

	QTcpServer * httpListener;
	QTcpServer * xmppListener;
	bool httpApi;
	bool httpVioletApi;
};

#endif
