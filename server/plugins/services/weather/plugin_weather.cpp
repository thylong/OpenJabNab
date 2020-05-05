#include <QDateTime>
#include <QCryptographicHash>
#include <QXmlStreamReader>
#include <QNetworkAccessManager>
#include <QNetworkRequest>
#include <QNetworkReply>
#include <QMapIterator>
#include <QRegExp>
#include <memory>
#include "account.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "httprequest.h"
#include "log.h"
#include "cron.h"
#include "messagepacket.h"
#include "sentencemanager.h"
#include "plugin_weather.h"
#include "settings.h"
#include "translator.h"
#include "ttsmanager.h"

#include <QJsonDocument>

PluginWeather::PluginWeather():PluginInterface("weather", "Current weather and forecasts", BunnyV2Plugin | ApiPlugin | SingleClickPlugin | CronPlugin | RfidPlugin | VoicePlugin | DevPlugin)
{
}

PluginWeather::~PluginWeather()
{
	Cron::UnregisterAll(this);
}

void PluginWeather::OnCron(Bunny * b, QVariant v, unsigned int)
{
	QString ville = v.value<QString>();
	getWeatherForCity(b, ville);
}

QString PluginWeather::OnApiGet(Bunny *b, QVariant v)
{
	QString ville = v.value<QString>();
	if(ville == QString())
	{
		ville = b->GetPluginSetting(GetName(), "Default/City", "").toString();
	}
	getWeatherForCity(b, ville);
	return QString();
}

bool PluginWeather::OnRFID(Bunny * b, QByteArray const& tag)
{
	QString city = b->GetPluginSetting(GetName(), QString("RFIDWeather/%1").arg(QString(tag.toHex())), QString()).toString();
	if(city != "")
	{
		getWeatherForCity(b, city);
		return true;
	}
	return false;
}

bool PluginWeather::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	if (type == PluginInterface::SingleClick)
	{
		QString city = b->GetPluginSetting(GetName(), "Default/City", "").toString();
		if(city != "")
		{
			getWeatherForCity(b, city);
			return true;
		}
	}
	return false;
}

bool PluginWeather::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("weather,forecast,forecasts", b), command))
	{
		QString city = b->GetPluginSetting(GetName(), "Default/City", "").toString();
		if(city != "")
		{
			getWeatherForCity(b, city);
			return true;
		}
	}
	return false;
}

QHash<QString, QString> PluginWeather::GetVoiceCommands(QString lng)
{
        QHash<QString, QString> list;
	foreach(QString keyword, Translator::tr("weather,forecast,forecasts", lng).split(","))
	{
               	list.insert(keyword, Translator::tr("Say weather for default city", lng));
	}
	return list;
}

void PluginWeather::getWeatherForCity(Bunny * b, QString ville)
{
	int villeId = ville.toInt();
	if(QString::number(villeId) != ville)
	{
    LogDebug("Bunny " + QString(b->GetID()) + " has an invalid city : " + ville);
    return;
  }

  QFile jsonFile(GetLocalHTTPFolder()->absoluteFilePath("weather.json"));
  if(!jsonFile.open(QIODevice::ReadOnly))
  {
    LogDebug("No Weather data file");
    return;
  }
  const auto& json = QJsonDocument::fromJson(jsonFile.readAll()).object();
  if(!json.contains(ville))
  {
    LogDebug(QString("No Weather data for city: %1").arg(ville));
    return;
  }
  const auto& jsonC = json[ville].toObject();
  const auto& sCity = jsonC["city"].toString();
  //LogDebug(QString("Got Weather data for city: %1: %2 !").arg(ville).arg(sCity));

  const auto& jsonCur = jsonC["current"].toObject();
  const auto& jsonFor = jsonC["forecast"].toObject();
  const auto& current = jsonCur["code"].toInt();
  const auto& iWind   = jsonCur["wind"].toInt();
  const auto& iCurrentTemp = jsonCur["temp"].toInt();
  const auto forecastT = jsonFor["code"].toInt();
	if(current == 3200 && forecastT == 3200)
	{
		LogError(QString("Unknow weather in %1 for bunny %2").arg(sCity, QString(b->GetID())));
    return;
	}

	QString language = b->GetPluginSetting(GetName(), "Lang","fr").toString();
	if(!GetLanguages().contains(language))
	{
    LogError(QString("No available language for weather in %1 for bunny %2").arg(language, QString(b->GetID())));
    return;
  }
	QString voice = TTSManager::GetBestVoice(b->GetVoice(), language);

  QByteArray message;
	// city, code, temp1, temp2
	if(current != 3200)
	{
		int code = GetWeatherFromCode(current);
		QString msg = GetTranslatedWeather(code, "current", language);
		if(msg.length())
		{
			QString sWind = insertWindData(GetTranslatedWind(GetWindFromSpeed(iWind), language), iWind);
			QString string = insertWeatherData(msg, sCity, sWind, iCurrentTemp, 0);
			TTSAnswer currentMsg = TTSManager::CreateSound(string, voice, language);
			TTSLog(b->GetID(), GetName(), currentMsg);
 			message += "MU " + currentMsg.file.toLatin1() + "\nMW\n";
		}
	}

  if(jsonCur.contains("forecast"))
	{
    const auto& jsonForC = jsonCur["forecast"].toObject();
    const auto forecastC = jsonForC["code"].toInt();
    if(forecastC != 3200)
    {
      int code = GetWeatherFromCode(forecastC);
      QString msg = GetTranslatedWeather(code, "forecast", language);
      if(msg.length())
      {
        const auto forecastL = jsonForC["min"].toInt();
        const auto forecastH = jsonForC["max"].toInt();
        QString string = insertWeatherData(msg, sCity, NULL, forecastL, forecastH);
        TTSAnswer forecastMsg = TTSManager::CreateSound(string, voice, language);
        TTSLog(b->GetID(), GetName(), forecastMsg);
        message += "MU " + forecastMsg.file.toLatin1() + "\nMW\n";
      }
    }
  }
  if(forecastT != 3200)
	{
		int code = GetWeatherFromCode(forecastT);
		QString msg = GetTranslatedWeather(code, "tomorrow", language);
		if(msg.length())
    {
      const auto forecastL = jsonFor["min"].toInt();
      const auto forecastH = jsonFor["max"].toInt();
			QString string = insertWeatherData(msg, sCity, NULL, forecastL, forecastH);
			TTSAnswer forecastMsg = TTSManager::CreateSound(string, voice, language);
			TTSLog(b->GetID(), GetName(), forecastMsg);
 			message += "MU " + forecastMsg.file.toLatin1() + "\nMW\n";
		}
  }
  if(b->IsIdle())
	{
     //LogDebug(QString("Weather Message %1").arg(QString(message)));
		 b->SendPacket(MessagePacket(message), GetName());
	}
}

void PluginWeather::OnBunnyConnect(Bunny * b)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext())
	{
		i.next();
		QString time = i.key();
		QString webcast = i.value().toString();
		if(webcast.toInt() != 0)
		{
			//Cron::RegisterDaily(this, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(webcast.at(0)));
			Cron::RegisterDaily(this, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(webcast));
		}
		else
		{
			LogInfo(QString("Removing weather city %1 for '%2'").arg(i.value().toString(), QString(b->GetID())));
			list.remove(time);
			b->SetPluginSetting(GetName(), "Webcasts", list);
		}
	}
}

void PluginWeather::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginWeather::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("addrfid(tag,city)", PluginWeather, Api_AddRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("removerfid(tag)", PluginWeather, Api_RemoveRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("listrfid()", PluginWeather, Api_ListRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("addcity(city,name)", PluginWeather, Api_addCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("removecity(city)", PluginWeather, Api_removeCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("getcitieslist()", PluginWeather, Api_getCitiesList);
	DECLARE_PLUGIN_BUNNY_API_CALL("setdefaultcity(city)", PluginWeather, Api_setDefaultCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("getdefaultcity()", PluginWeather, Api_getDefaultCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("addwebcast(time,city)", PluginWeather, Api_AddWebcast);
	DECLARE_PLUGIN_BUNNY_API_CALL("removewebcast(time)", PluginWeather, Api_RemoveWebcast);
	DECLARE_PLUGIN_BUNNY_API_CALL("getwebcastslist()", PluginWeather, Api_ListWebcast);
	DECLARE_PLUGIN_BUNNY_API_CALL("setlang(lg)", PluginWeather, Api_setLang);
	DECLARE_PLUGIN_BUNNY_API_CALL("getlang()", PluginWeather, Api_getLang);
	DECLARE_PLUGIN_BUNNY_API_CALL("setfreq(f)", PluginWeather, Api_setFrequency);
	DECLARE_PLUGIN_BUNNY_API_CALL("getfreq()", PluginWeather, Api_getFrequency);

	DECLARE_PLUGIN_API_CALL("setgroup(id,name)", PluginWeather, Api_setConditionGroup);
	DECLARE_PLUGIN_API_CALL("getgroup()", PluginWeather, Api_getConditionGroup);
	DECLARE_PLUGIN_API_CALL("setcondition(id,group)", PluginWeather, Api_setCondition);
	DECLARE_PLUGIN_API_CALL("getcondition(group)", PluginWeather, Api_getCondition);
	DECLARE_PLUGIN_API_CALL("getconditions()", PluginWeather, Api_getConditions);
	DECLARE_PLUGIN_API_CALL("settranslation(id,lng,when,tr)", PluginWeather, Api_setTranslation);
	DECLARE_PLUGIN_API_CALL("gettranslation(id,lng,when)", PluginWeather, Api_getTranslation);
	DECLARE_PLUGIN_API_CALL("translation()", PluginWeather, Api_Translation);
  DECLARE_PLUGIN_API_CALL("getCitiesList()", PluginWeather, Api_GetAllCitiesList);
}

PLUGIN_API_CALL(PluginWeather::Api_GetAllCitiesList) {
  if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

  QMap<QString, QVariant> list;
  const auto& bunnies = BunnyManager::GetAllBunnies();

    QHashIterator<QByteArray, Bunny*> bunny(bunnies);
    while(bunny.hasNext())
    {
      bunny.next();
      auto* b = bunny.value();
      if(b->HasPlugin(this) && b->IsConnected())
        foreach(QString city, b->GetPluginSetting(GetName(), "Cities", QStringList()).toStringList())
        {
          list.insert(city, GetSettings("Cities/" + city, QString()));
        }
    }


	return new ApiManager::ApiMappedList(list);
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_ListRFID)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QStringList settings = bunny->GetPluginSettings(GetName());
	QRegExp rx("RFIDWeather/([a-fA-F0-9]+)$");
        QMap<QString, QVariant> list;

	foreach(QString key, settings)
	{
        	if(rx.indexIn(key) != -1)
	        {
			list.insert(QString(rx.cap(1)), bunny->GetPluginSetting(GetName(), key, QString()));
		}
	}
        return new ApiManager::ApiMappedList(list);
}

PLUGIN_API_CALL(PluginWeather::Api_setConditionGroup) {
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	int id = hRequest.GetArg("id").toInt();
	QString name = hRequest.GetArg("name");

	if(hRequest.HasArg("wind"))
	{
		QStringList list = GetSettings("List/Winds", QStringList()).toStringList();
		SetSettings("Wind" + QString::number(id) + "/Name", name);
		list << QString::number(id);
		list.removeDuplicates();
		list.sort();
		SetSettings("List/Winds", list);
		return new ApiManager::ApiOk(QString("Wind group '%1' is now named '%2'").arg(QString::number(id), name));
	}
	else
	{
		QStringList list = GetSettings("List/Groups", QStringList()).toStringList();
		SetSettings("Group" + QString::number(id) + "/Name", name);
		list << QString::number(id);
		list.removeDuplicates();
		list.sort();
		SetSettings("List/Groups", list);
		return new ApiManager::ApiOk(QString("Group '%1' is now named '%2'").arg(QString::number(id), name));
	}

}

PLUGIN_API_CALL(PluginWeather::Api_getConditionGroup) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	QMap<QString, QVariant> list;
	if(hRequest.HasArg("wind"))
	{
		QStringList keys = GetSettings("List/Winds", QStringList()).toStringList();
		foreach(QString key, keys)
			list.insert(key, GetSettings("Wind" + key + "/Name", QString()));
	}
	else
	{
		QStringList keys = GetSettings("List/Groups", QStringList()).toStringList();
		foreach(QString key, keys)
			list.insert(key, GetSettings("Group" + key + "/Name", QString()));
	}

	return new ApiManager::ApiMappedList(list);
}

PLUGIN_API_CALL(PluginWeather::Api_setCondition) {
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	int id = hRequest.GetArg("id").toInt();
	int group = hRequest.GetArg("group").toInt();

	if(hRequest.HasArg("wind"))
	{
		SetSettings("Wind" + QString::number(group) + "/Min", id);
		QStringList list;
		QStringList keys = GetSettings("List/Winds", QStringList()).toStringList();
		foreach(QString key, keys)
			list << GetSettings("Wind" + key + "/Min", QString()).toString();
		SetSettings("List/Speed", list);

		return new ApiManager::ApiOk(QString("Wind group '%2' starts at %1 km/h").arg(QString::number(id), QString::number(group)));
	}
	else
	{
		int old = GetSettings("Conditions/" + QString::number(id), -1).toInt();
		if(old != -1)
		{
			QStringList oldList = GetSettings("Group" + QString::number(old) + "/Conditions", QStringList()).toStringList();
			oldList.removeAll(QString::number(id));
			SetSettings("Group" + QString::number(old) + "/Conditions", oldList);
		}
		QStringList list = GetSettings("Group" + QString::number(group) + "/Conditions", QStringList()).toStringList();
		list << QString::number(id);
		list.removeDuplicates();
		list.sort();
		SetSettings("Group" + QString::number(group) + "/Conditions", list);
		SetSettings("Conditions/" + QString::number(id), group);
		QStringList conditions = GetSettings("List/Conditions", QStringList()).toStringList();
		conditions << QString::number(id);
		SetSettings("List/Conditions", conditions);

		return new ApiManager::ApiOk(QString("Condition '%1' is now in group '%2'").arg(QString::number(id), QString::number(group)));
	}
}

PLUGIN_API_CALL(PluginWeather::Api_getCondition) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	int group = hRequest.GetArg("group").toInt();

	if(hRequest.HasArg("wind"))
	{
		return new ApiManager::ApiString(GetSettings("Wind" + QString::number(group) + "/Min", "0").toString());
	}
	else
	{
		QStringList list = GetSettings("Group" + QString::number(group) + "/Conditions", QStringList()).toStringList();

		return new ApiManager::ApiList(list);
	}
}

PLUGIN_API_CALL(PluginWeather::Api_getConditions) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	QMap<QString, QVariant> list;
	if(hRequest.HasArg("wind"))
	{
		QStringList keys = GetSettings("List/Speed", QStringList()).toStringList();
		QStringList winds = GetSettings("List/Winds", QStringList()).toStringList();
		foreach(QString key, keys)
		{
			list.insert(key, winds.at(keys.indexOf(key)));
		}
	}
	else
	{
		QStringList keys = GetSettings("List/Conditions", QStringList()).toStringList();
		foreach(QString key, keys)
			list.insert(key, GetSettings("Conditions/" + key, QString()));
	}

	return new ApiManager::ApiMappedList(list);
}

PLUGIN_API_CALL(PluginWeather::Api_Translation) {
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("when"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("when", GetName()));

	QString when = hRequest.GetArg("when");

	if(!hRequest.HasArg("id"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("id", GetName()));

	int id = hRequest.GetArg("id").toInt();

	if(!hRequest.HasArg("lng"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

	QString lng = hRequest.GetArg("lng");

	if(action == "list")
	{
		QStringList list;
		if(hRequest.HasArg("wind"))
			list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
		else
			list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();

		return new ApiManager::ApiList(list);
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("tr"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tr", GetName()));

		QString tr = hRequest.GetArg("tr");

		if(hRequest.HasArg("wind"))
		{
			QStringList list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list << tr;
			list.removeDuplicates();
			list.sort();
			SetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiManager::ApiOk(QString("Added translation in wind group '%1'").arg(QString::number(id)));
		}
		else
		{
			QStringList list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list << tr;
			list.removeDuplicates();
			list.sort();
			SetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiManager::ApiOk(QString("Added translation in group '%1'").arg(QString::number(id)));
		}
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("tr"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tr", GetName()));

		QString tr = hRequest.GetArg("tr");

		if(hRequest.HasArg("wind"))
		{
			QStringList list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list.removeAll(tr);
			list.sort();
			SetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiManager::ApiOk(QString("Removed translation in wind group '%1'").arg(QString::number(id)));
		}
		else
		{
			QStringList list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list.removeAll(tr);
			list.sort();
			SetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiManager::ApiOk(QString("Removed translation in group '%1'").arg(QString::number(id)));
		}
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginWeather::Api_setTranslation) {
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	int id = hRequest.GetArg("id").toInt();
	QString lng = hRequest.GetArg("lng");
	QString tr = hRequest.GetArg("tr");
	QString when = hRequest.GetArg("when");

	if(hRequest.HasArg("wind"))
	{
		QStringList list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
		list << tr;
		list.removeDuplicates();
		list.sort();
		SetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, list);
		return new ApiManager::ApiOk(QString("Added translation in wind group '%1'").arg(QString::number(id)));
	}
	else
	{
		QStringList list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
		list << tr;
		list.removeDuplicates();
		list.sort();
		SetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, list);
		return new ApiManager::ApiOk(QString("Added translation in group '%1'").arg(QString::number(id)));
	}

}

PLUGIN_API_CALL(PluginWeather::Api_getTranslation) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	int id = hRequest.GetArg("id").toInt();
	QString lng = hRequest.GetArg("lng");
	QString when = hRequest.GetArg("when");

	QStringList list;
	if(hRequest.HasArg("wind"))
		list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
	else
		list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();

	return new ApiManager::ApiList(list);
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_addCity) {
	Q_UNUSED(account);

	if(!hRequest.HasArg("city"))
		return new ApiManager::ApiError(QString("Missing argument 'city' for plugin Weather"));
	QString city = hRequest.GetArg("city");
	QStringList list = bunny->GetPluginSetting(GetName(), "Cities", QStringList()).toStringList();
	list.append(city);
	bunny->SetPluginSetting(GetName(), "Cities", list);
	SetSettings("Cities/" + city, hRequest.GetArg("name"));

	return new ApiManager::ApiOk(QString("Added city '%1' for bunny '%2'").arg(city, QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_removeCity)
{
	Q_UNUSED(account);

	if(!hRequest.HasArg("city"))
		return new ApiManager::ApiError(QString("Missing argument 'city' for plugin Weather"));

	QString city = hRequest.GetArg("city");
	QStringList list = bunny->GetPluginSetting(GetName(), "Cities", QStringList()).toStringList();
	list.removeAll(city);
	bunny->SetPluginSetting(GetName(), "Cities", list);

	return new ApiManager::ApiOk(QString("Removed city '%1' for bunny '%2'").arg(city, QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getCitiesList) {
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QMap<QString, QVariant> list;
	foreach(QString city, bunny->GetPluginSetting(GetName(), "Cities", QStringList()).toStringList())
	{
		list.insert(city, GetSettings("Cities/" + city, QString()));
	}
	return new ApiManager::ApiMappedList(list);
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_setDefaultCity)
{
	Q_UNUSED(account);

	if(!hRequest.HasArg("city"))
		return new ApiManager::ApiError(QString("Missing argument 'city' for plugin Weather"));

	bunny->SetPluginSetting(GetName(), "Default/City", hRequest.GetArg("city"));
	return new ApiManager::ApiOk(QString("New default city defined '%1' for bunny '%2'").arg(hRequest.GetArg("city"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getDefaultCity)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "Default/City",QString()).toString());
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_AddWebcast)
{
	Q_UNUSED(account);

	QString hTime = hRequest.GetArg("time");
	QString city = hRequest.GetArg("city");
	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap();
	if(!list.contains(hTime))
	{
		Cron::RegisterDaily(this, Cron::mkTime(hTime), bunny, Cron::Classic, QVariant::fromValue(city));
		list.insert(hTime,city);
		bunny->SetPluginSetting(GetName(), "Webcasts", list);
		return new ApiManager::ApiOk(QString("Add webcast at '%1' to bunny '%2'").arg(hTime, QString(bunny->GetID())));
	}
	return new ApiManager::ApiError(QString("Webcast already exists at '%1' for bunny '%2'").arg(hTime, QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_RemoveWebcast)
{
	Q_UNUSED(account);

	if(!hRequest.HasArg("time"))
		return new ApiManager::ApiError(QString("Missing argument 'time' for plugin Weather"));

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap();
	QString time = hRequest.GetArg("time");
	if(list.contains(time))
	{
		list.remove(time);
		bunny->SetPluginSetting(GetName(), "Webcasts", list);

		// Recreate crons
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);
		return new ApiManager::ApiOk(QString("Remove webcast at '%1' for bunny '%2'").arg(hRequest.GetArg("time"), QString(bunny->GetID())));
	}
	return new ApiManager::ApiError(QString("No webcast at '%1' for bunny '%2'").arg(hRequest.GetArg("time"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_ListWebcast)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap());
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_AddRFID)
{
	Q_UNUSED(account);

	bunny->SetPluginSetting(GetName(), QString("RFIDWeather/%1").arg(hRequest.GetArg("tag")), hRequest.GetArg("city"));

	return new ApiManager::ApiOk(QString("Add weather for '%1' for RFID '%2', bunny '%3'").arg(hRequest.GetArg("city"), hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_RemoveRFID)
{
	Q_UNUSED(account);

	bunny->RemovePluginSetting(GetName(), QString("RFIDWeather/%1").arg(hRequest.GetArg("tag")));

	return new ApiManager::ApiOk(QString("Remove RFID '%2' for bunny '%3'").arg(hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getLang)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "Lang","fr").toString());
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_setLang)
{
	Q_UNUSED(account);
	bunny->SetPluginSetting(GetName(), "Lang",hRequest.GetArg("lg"));

	return new ApiManager::ApiOk("Lang Updated!");
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getFrequency)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "Frequency", 0).toString());
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_setFrequency)
{
	Q_UNUSED(account);
	bunny->SetPluginSetting(GetName(), "Frequency",hRequest.GetArg("f"));
	OnBunnyDisconnect(bunny);
	OnBunnyConnect(bunny);

	return new ApiManager::ApiOk("Frequency updated!");
}

int PluginWeather::GetWeatherFromCode(int code)
{
	return GetSettings("Conditions/" + QString::number(code), 0).toInt();
}

QString PluginWeather::GetTranslatedWeather(int code, QString time, QString lng)
{
  //LogDebug(QString("Code :%1, Time %2, Lng: %3").arg(code).arg(time,lng));
	QString newCode = "plugin_weather_";
	newCode += time == "current" ? "condition_" : (time == "forecast" ? "forecast_" : "tomorrow_");
	switch(code)
	{
		case PLUGIN_WEATHER_SUNNY:
			newCode += "sunny";
			break;
		case PLUGIN_WEATHER_RAIN:
			newCode += "rain";
			break;
		case PLUGIN_WEATHER_SNOW:
			newCode += "snow";
			break;
		case PLUGIN_WEATHER_STORM:
			newCode += "storm";
			break;
		case PLUGIN_WEATHER_RAINSNOW:
			newCode += "rainsnow";
			break;
		case PLUGIN_WEATHER_FOG:
			newCode += "fog";
			break;
		case PLUGIN_WEATHER_WIND:
			newCode += "wind";
			break;
		case PLUGIN_WEATHER_CLOUD:
			newCode += "cloud";
			break;
		default:
		case PLUGIN_WEATHER_UNKNOW:
			newCode += "unknow";
			break;
	}
	QStringList sentences = SentenceManager::GetSentences(newCode, lng);

	//sentences << GetSettings("Group" + QString::number(code) + "/" + time + "_" + lng, QStringList()).toStringList();
	sentences.removeDuplicates();

	if(sentences.count())
	{
		return sentences.at( qrand() % sentences.count() );
	}
	return "";
}

QString PluginWeather::insertWindData(QString str, int wind)
{
	return str.replace("WIND", QString::number(wind));
}

QString PluginWeather::insertWeatherData(QString str, QString city, QString wind, int minTemp, int maxTemp)
{
	str = str.replace("CITY", city);
	str = str.replace("WIND", wind);
	str = str.replace("MINTEMP", QString::number(minTemp)).replace("MAXTEMP", QString::number(maxTemp)).replace("TEMP", QString::number(minTemp));
	return str;
}

int PluginWeather::GetWindFromSpeed(int speed)
{
	QStringList winds = GetSettings("List/Speed", QStringList()).toStringList();
	foreach(QString min, winds)
	{
		if(speed < min.toInt())
		{
			int index = winds.indexOf(min);
			return GetSettings("List/Speed", QStringList()).toStringList().at(index).toInt();
		}
	}
	return -1;
}

QString PluginWeather::GetTranslatedWind(int code, QString lng)
{
	QStringList sentences = GetSettings("Wind" + QString::number(code) + "/" + lng, QStringList()).toStringList();
	if(sentences.count())
	{
		return sentences.at( qrand() % sentences.count() );
	}
	return "";
}

/*
0 inconnue
1 beau
2 couvert
3 pluie
4 tempete
5 neige
6 brouillard
7 vent
*/

/*
Code 	Description
0 	tornade
1 	tempête tropicale
2 	ouragan
3 	orages violents
4 	orages
5 	la pluie et la neige
6 	la pluie et la neige fondue mixte
7 	mélée de neige et le grésil
8 	bruine verglaçante
9 	bruine
10 	pluie verglaçante
11 	douches
12 	douches
13 	averses de neige
14 	légères averses de neige
15 	poudrerie
16 	neige
17 	grêle
18 	neige fondue
19 	poussière
20 	brumeux
21 	brume
22 	enfumé
23 	de tempête
24 	venteux
25 	froid
26 	nuageux
27 	la plupart du temps nuageux (nuit)
28 	la plupart du temps nuageux (jour)
29 	partiellement nuageux (nuit)
30 	partiellement nuageux (jour)
31 	effacer (nuit)
32 	ensoleillé
33 	équitable (nuit)
34 	équitable (jour)
35 	la pluie et la grêle mixte
36 	chaud
37 	orages isolés
38 	orages dispersés
39 	orages dispersés
40 	averses intermittentes
41 	fortes chutes de neige
42 	averses de neige éparses
43 	fortes chutes de neige
44 	partiellement nuageux
45 	orages
46 	averses de neige
47 	orages isolés
3200 	pas disponible
*/

/*
<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
		<rss version="2.0" xmlns:yweather="http://xml.weather.yahoo.com/ns/rss/1.0" xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#">
			<channel>

<title>Yahoo! Weather - Bucharest, RO</title>
<link>http://us.rd.yahoo.com/dailynews/rss/weather/Bucharest__RO/ *http://weather.yahoo.com/forecast/ROXX0003_c.html</link>
<description>Yahoo! Weather for Bucharest, RO</description>
<language>en-us</language>
<lastBuildDate>Thu, 31 Jan 2013 4:58 pm ET</lastBuildDate>
<ttl>60</ttl>
<yweather:location city="Bucharest" region=""   country="Romania"/>
<yweather:units temperature="C" distance="km" pressure="mb" speed="km/h"/>
<yweather:wind chill="1"   direction="0"   speed="0" />
<yweather:atmosphere humidity="96"  visibility="1"  pressure="1011"  rising="0" />
<yweather:astronomy sunrise="7:33 am"   sunset="5:17 pm"/>
<image>
<title>Yahoo! Weather</title>
<width>142</width>
<height>18</height>
<link>http://weather.yahoo.com</link>
<url>http://l.yimg.com/a/i/brand/purplelogo//uh/us/news-wea.gif</url>
</image>
<item>
<title>Conditions for Bucharest, RO at 4:58 pm ET</title>
<geo:lat>44.43</geo:lat>
<geo:long>26.1</geo:long>
<link>http://us.rd.yahoo.com/dailynews/rss/weather/Bucharest__RO/ *http://weather.yahoo.com/forecast/ROXX0003_c.html</link>
<pubDate>Thu, 31 Jan 2013 4:58 pm ET</pubDate>
<yweather:condition  text="Cloudy"  code="26"  temp="1"  date="Thu, 31 Jan 2013 4:58 pm ET" />
<description><![CDATA[
<img src="http://l.yimg.com/a/i/us/we/52/26.gif"/><br />
<b>Current Conditions:</b><br />
Cloudy, 1 C<BR />
<BR /><b>Forecast:</b><BR />
Thu - Mostly Clear. High: 6 Low: -2<br />
Fri - Partly Cloudy/Wind. High: 8 Low: -1<br />
<br />
<a href="http://us.rd.yahoo.com/dailynews/rss/weather/Bucharest__RO/ *http://weather.yahoo.com/forecast/ROXX0003_c.html">Full Forecast at Yahoo! Weather</a><BR/><BR/>
(provided by <a href="http://www.weather.com" >The Weather Channel</a>)<br/>
]]></description>
<yweather:forecast day="Thu" date="31 Jan 2013" low="-2" high="6" text="Mostly Clear" code="33" />
<yweather:forecast day="Fri" date="1 Feb 2013" low="-1" high="8" text="Partly Cloudy/Wind" code="24" />
<guid isPermaLink="false">ROXX0003_2013_02_01_7_00_ET</guid>
</item>
</channel>
</rss>

<!-- api10.weather.ch1.yahoo.com Thu Jan 31 16:02:54 PST 2013 -->

*/
