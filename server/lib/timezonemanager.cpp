#include "account.h"
#include "timezone.h"
#include "timezonemanager.h"
#include "translator.h"
#include "settings.h"

TimezoneManager::TimezoneManager()
{
  const QString tzPath = GlobalSettings::GetConfigDir().append("/timezones/");
	timezonesDir = QDir(tzPath);
  if(!timezonesDir.exists())
  {
    if(!timezonesDir.mkpath(tzPath))
    {
      LogError("Unable to create timezones directory !\n");
			exit(-1);
    }
  }
}

TimezoneManager & TimezoneManager::Instance()
{
  static TimezoneManager b;
  return b;
}

TimezoneManager::~TimezoneManager()
{
	foreach(Timezone * t, listOfTimezones)
		delete t;
}

void TimezoneManager::LoadAllTimezones()
{
	LogInfo(QString("Finding timezones in : %1").arg(timezonesDir.path()));
	QStringList filters;
	filters << "*.dat";
	timezonesDir.setNameFilters(filters);
	foreach (QFileInfo file, timezonesDir.entryInfoList(QDir::Files))
	{
		GetTimezone(file.baseName().replace("_", "/"));
	}

	Timezone * t = new Timezone("UTC/",timezonesDir);
        t->SetStdCode("UTC");
        t->SetStdName("Universal Time Coordinated");
        t->SetStdOffset(0);

	listOfTimezones.insert("UTC", t);
}

QHash<QString, Timezone *> TimezoneManager::GetTimezonesList(void)
{
	return Instance().listOfTimezones;
}

void TimezoneManager::InitApiCalls()
{
	DECLARE_API_CALL("getListOfTimezones()", &TimezoneManager::Api_GetListOfTimezones);
	DECLARE_API_CALL("addTimezone(area,location)", &TimezoneManager::Api_AddTimezone);
	DECLARE_API_CALL("removeTimezone(area,location)", &TimezoneManager::Api_RemoveTimezone);
}

Timezone * TimezoneManager::GetTimezone(QString const& area, QString const& location)
{
	return GetTimezone(area + "/" + location);
}

Timezone * TimezoneManager::GetServerTimezone()
{
	QString zone = GlobalSettings::Get("Config/TimeZone", "UTC").toString();
	if(listOfTimezones.contains(zone))
		return listOfTimezones.value(zone);

	return listOfTimezones.value("UTC");
}

Timezone * TimezoneManager::GetTimezone(QString const& zone)
{
	if(listOfTimezones.contains(zone))
		return listOfTimezones.value(zone);

	Timezone * t = new Timezone(zone,TimezoneManager::Instance().timezonesDir);
	listOfTimezones.insert(t->GetZone(), t);
	return t;
}

void TimezoneManager::Close()
{
	foreach(Timezone * t, listOfTimezones)
		delete t;
	listOfTimezones.clear();
}

API_CALL(TimezoneManager::Api_GetListOfTimezones) {
	Q_UNUSED(hRequest);
	Q_UNUSED(account);

	QMap<QString, QVariant> list;
	foreach(Timezone * t, listOfTimezones)
	{
		foreach(QString alias, t->GetAliases())
		{
			list.insert(t->GetZone() + ";" + alias, alias);
		}
	}
	return new ApiManager::ApiMappedList(list);
}

API_CALL(TimezoneManager::Api_AddTimezone) {
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString area = hRequest.GetArg("area");
	QString location = hRequest.GetArg("location");
	if(listOfTimezones.contains(area + "/" + location))
		return new ApiManager::ApiError(Translator::tr("Timezone already exists", account));

	GetTimezone(area + "/" + location);
	return new ApiManager::ApiOk(Translator::tr("Timezone successfully added", account));
}

API_CALL(TimezoneManager::Api_RemoveTimezone)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString area = hRequest.GetArg("area");
	QString location = hRequest.GetArg("location");
	if(!listOfTimezones.contains(area + "/" + location))
		return new ApiManager::ApiError(Translator::tr("Timezone '%1' does not exist", account).arg(area + "/" + location));

	Timezone * t = listOfTimezones.value(area + "/" + location);
	delete t;
	listOfTimezones.remove(area + "/" + location);
	QFile timezoneFile(timezonesDir.absoluteFilePath(QString("%1_%2.dat").arg(area, location)));
	if(timezoneFile.remove())
		return new ApiManager::ApiOk(Translator::tr("Timezone %1 removed", account).arg(area + "/" + location));
	return new ApiManager::ApiError(Translator::tr("Error when removing timezone %1", account).arg(area + "/" + location));
}

QHash<QString, Timezone *> TimezoneManager::listOfTimezones;
