#ifndef _APIMANAGER_H_
#define _APIMANAGER_H_

#include <QByteArray>
#include <QDateTime>
#include <QList>
#include <QMap>
#include <QMapIterator>
#include <QString>
#include <QVariant>
#include "global.h"
#include "apihandler.h"

class Account;
class AccountManager;
class HTTPRequest;
class PluginManager;

class OJN_EXPORT ApiManager
	: public ApiHandler<ApiManager>
{
public:
	static ApiManager & Instance();
	static void InitApiCalls(void);
	ApiAnswers::Answer* ProcessApiCall(QString const&, HTTPRequest &);
	static int getUptime();

private:
	ApiManager();
	ApiAnswers::Answer* ProcessPluginApiCall(QString const&, HTTPRequest const&, Account const&);
	ApiAnswers::Answer* ProcessBunnyApiCall( QString const&, HTTPRequest const&, Account const&);
	ApiAnswers::Answer* ProcessZtampApiCall( QString const&, HTTPRequest const&, Account const&);
	ApiAnswers::Answer* ProcessBunnyVioletApiCall(QString const&, HTTPRequest const&);
	int startTime;

	API_CALL(Api_About);
	API_CALL(Api_Config);
	API_CALL(Api_Ping);
	API_CALL(Api_Stats);
	API_CALL(Api_System);
	API_CALL(Api_Uptime);
};
#endif
