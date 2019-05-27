#ifndef _TIMEZONEMANAGER_H_
#define _TIMEZONEMANAGER_H_

#include <QHash>
#include <QVector>
#include <QDir>
#include "global.h"
#include "apihandler.h"
#include "apimanager.h"

class Account;
class Timezone;
class OJN_EXPORT TimezoneManager : public ApiHandler<TimezoneManager>
{
	friend class ApiManager;
public:
	static TimezoneManager & Instance();

	static Timezone * GetServerTimezone();
	static Timezone * GetTimezone(QString const&);
	static Timezone * GetTimezone(QString const&, QString const&);
	static void Init();
	static void LoadTimezones();
	static void Close();

	static QHash<QString, Timezone *> GetTimezonesList(void);

	// API
	static void InitApiCalls();

protected:
	// API
	API_CALL(Api_GetListOfTimezones);
	API_CALL(Api_AddTimezone);
	API_CALL(Api_RemoveTimezone);
	virtual ~TimezoneManager();

private:
	TimezoneManager();
	void LoadAllTimezones();
	QDir timezonesDir;
	static QHash<QString, Timezone *> listOfTimezones;
};

inline void TimezoneManager::Init()
{
	InitApiCalls();
}

inline void TimezoneManager::LoadTimezones()
{
	Instance().LoadAllTimezones();
}

#endif
