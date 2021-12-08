#include <memory>

#include <QDateTime>
#include <QCryptographicHash>
#include <QRandomGenerator>
#include <QXmlStreamReader>
#include <QNetworkRequest>
#include <QNetworkReply>
#include <QMapIterator>
#include <QRegExp>
#include <QJsonDocument>

#include "plugin_weather.h"

#include "account.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "log.h"
#include "packets/messagepacket.h"
#include "sentencemanager.h"
#include "settings.h"
#include "translator.h"
#include "tts/ttsmanager.h"

PluginWeather::PluginWeather()
	: PluginInterface("weather", "Current weather and forecasts",
					          BunnyV2Plugin | ApiPlugin | SingleClickPlugin | CronPlugin | RfidPlugin | VoicePlugin | DevPlugin)
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
  QFile jsonFile(GetLocalHTTPFolder()->absoluteFilePath("cache_weatherapi.json"));
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

	QString language = b->GetPluginSetting(GetName(), "Lang","fr").toString();
	if(!GetLanguages().contains(language))
	{
    LogError(QString("No available language for weather in %1 for bunny %2").arg(language, QString(b->GetID())));
    return;
  }
	QString voice = TTSManager::GetBestVoice(b->GetVoice(), language);

  QByteArray message;
	// city, code, temp1, temp2
	if(jsonC.contains("current"))
	{
		const auto& jsonCur = jsonC["current"].toObject();
		int code = GetWeatherFromCode(jsonCur["code"].toInt());
		QString msg = GetTranslatedWeather(code, "current", language);
		if(msg.length())
		{
			const auto& iWind   = jsonCur["wind"].toDouble();
			const auto& iCurrentTemp = jsonCur["temp"].toDouble();
			QString sWind = insertWindData(GetTranslatedWind(GetWindFromSpeed(iWind), language), iWind);
			QString string = insertWeatherData(msg, sCity, sWind, iCurrentTemp, 0);
			TTSAnswer currentMsg = TTSManager::CreateSound(string, voice, language);
			TTSLog(b->GetID(), GetName(), currentMsg);
 			message += "MU " + currentMsg.file.toLatin1() + "\nMW\n";
		}
	}
	const auto& jsonFor = jsonC["forecast"].toObject();
	for(auto jsonForI = jsonFor.constBegin(); jsonForI != jsonFor.constEnd(); jsonForI++)
	{
    const auto& jsonForK = jsonForI.key();
		const auto& jsonForC = jsonForI.value();
    const auto forecastC = jsonForC["code"].toInt();
    if(forecastC == 0)
			continue; // FIXME !!
		int code = GetWeatherFromCode(forecastC);
		QString msg = GetTranslatedWeather(code, (jsonForK == "current" ? "forecast" : "tomorrow"), language);
		if(msg.length())
		{
			const auto forecastL = jsonForC["min"].toDouble();
			const auto forecastH = jsonForC["max"].toDouble();
			const auto& iWind    = jsonForC["wind"].toDouble();
			QString sWind = insertWindData(GetTranslatedWind(GetWindFromSpeed(iWind), language), iWind);
			QString string = insertWeatherData(msg, sCity, sWind, forecastL, forecastH);
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
	DECLARE_PLUGIN_BUNNY_API_CALL("addrfid(tag,city)", &PluginWeather::Api_AddRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("removerfid(tag)", &PluginWeather::Api_RemoveRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("listrfid()", &PluginWeather::Api_ListRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("addcity(city,name)", &PluginWeather::Api_addCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("removecity(city)", &PluginWeather::Api_removeCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("getcitieslist()", &PluginWeather::Api_getCitiesList);
	DECLARE_PLUGIN_BUNNY_API_CALL("setdefaultcity(city)", &PluginWeather::Api_setDefaultCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("getdefaultcity()", &PluginWeather::Api_getDefaultCity);
	DECLARE_PLUGIN_BUNNY_API_CALL("addwebcast(time,city)", &PluginWeather::Api_AddWebcast);
	DECLARE_PLUGIN_BUNNY_API_CALL("removewebcast(time)", &PluginWeather::Api_RemoveWebcast);
	DECLARE_PLUGIN_BUNNY_API_CALL("getwebcastslist()", &PluginWeather::Api_ListWebcast);
	DECLARE_PLUGIN_BUNNY_API_CALL("setlang(lg)", &PluginWeather::Api_setLang);
	DECLARE_PLUGIN_BUNNY_API_CALL("getlang()", &PluginWeather::Api_getLang);
	DECLARE_PLUGIN_BUNNY_API_CALL("setfreq(f)", &PluginWeather::Api_setFrequency);
	DECLARE_PLUGIN_BUNNY_API_CALL("getfreq()", &PluginWeather::Api_getFrequency);

	DECLARE_PLUGIN_API_CALL("setgroup(id,name)", &PluginWeather::Api_setConditionGroup);
	DECLARE_PLUGIN_API_CALL("getgroup()", &PluginWeather::Api_getConditionGroup);
	DECLARE_PLUGIN_API_CALL("setcondition(id,group)", &PluginWeather::Api_setCondition);
	DECLARE_PLUGIN_API_CALL("getcondition(group)", &PluginWeather::Api_getCondition);
	DECLARE_PLUGIN_API_CALL("getconditions()", &PluginWeather::Api_getConditions);
	DECLARE_PLUGIN_API_CALL("settranslation(id,lng,when,tr)", &PluginWeather::Api_setTranslation);
	DECLARE_PLUGIN_API_CALL("gettranslation(id,lng,when)", &PluginWeather::Api_getTranslation);
	DECLARE_PLUGIN_API_CALL("translation()", &PluginWeather::Api_Translation);
  DECLARE_PLUGIN_API_CALL("getCitiesList()", &PluginWeather::Api_GetAllCitiesList);
}

PLUGIN_API_CALL(PluginWeather::Api_GetAllCitiesList) {
  if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

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


	return new ApiAnswers::MappedList(list);
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
        return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginWeather::Api_setConditionGroup) {
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

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
		return new ApiAnswers::Ok(QString("Wind group '%1' is now named '%2'").arg(QString::number(id), name));
	}
	else
	{
		QStringList list = GetSettings("List/Groups", QStringList()).toStringList();
		SetSettings("Group" + QString::number(id) + "/Name", name);
		list << QString::number(id);
		list.removeDuplicates();
		list.sort();
		SetSettings("List/Groups", list);
		return new ApiAnswers::Ok(QString("Group '%1' is now named '%2'").arg(QString::number(id), name));
	}

}

PLUGIN_API_CALL(PluginWeather::Api_getConditionGroup) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiAnswers::Error("Access denied");

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

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginWeather::Api_setCondition) {
	if(!account.IsAdmin())
		return new ApiAnswers::Error("Access denied");

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

		return new ApiAnswers::Ok(QString("Wind group '%2' starts at %1 km/h").arg(QString::number(id), QString::number(group)));
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

		return new ApiAnswers::Ok(QString("Condition '%1' is now in group '%2'").arg(QString::number(id), QString::number(group)));
	}
}

PLUGIN_API_CALL(PluginWeather::Api_getCondition) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiAnswers::Error("Access denied");

	int group = hRequest.GetArg("group").toInt();

	if(hRequest.HasArg("wind"))
	{
		return new ApiAnswers::String(GetSettings("Wind" + QString::number(group) + "/Min", "0").toString());
	}
	else
	{
		QStringList list = GetSettings("Group" + QString::number(group) + "/Conditions", QStringList()).toStringList();

		return new ApiAnswers::List(list);
	}
}

PLUGIN_API_CALL(PluginWeather::Api_getConditions) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiAnswers::Error("Access denied");

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

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginWeather::Api_Translation) {
	if(!account.IsAdmin())
		return new ApiAnswers::Error("Access denied");

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("when"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("when", GetName()));

	QString when = hRequest.GetArg("when");

	if(!hRequest.HasArg("id"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("id", GetName()));

	int id = hRequest.GetArg("id").toInt();

	if(!hRequest.HasArg("lng"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

	QString lng = hRequest.GetArg("lng");

	if(action == "list")
	{
		QStringList list;
		if(hRequest.HasArg("wind"))
			list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
		else
			list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();

		return new ApiAnswers::List(list);
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("tr"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tr", GetName()));

		QString tr = hRequest.GetArg("tr");

		if(hRequest.HasArg("wind"))
		{
			QStringList list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list << tr;
			list.removeDuplicates();
			list.sort();
			SetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiAnswers::Ok(QString("Added translation in wind group '%1'").arg(QString::number(id)));
		}
		else
		{
			QStringList list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list << tr;
			list.removeDuplicates();
			list.sort();
			SetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiAnswers::Ok(QString("Added translation in group '%1'").arg(QString::number(id)));
		}
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("tr"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tr", GetName()));

		QString tr = hRequest.GetArg("tr");

		if(hRequest.HasArg("wind"))
		{
			QStringList list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list.removeAll(tr);
			list.sort();
			SetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiAnswers::Ok(QString("Removed translation in wind group '%1'").arg(QString::number(id)));
		}
		else
		{
			QStringList list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
			list.removeAll(tr);
			list.sort();
			SetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, list);
			return new ApiAnswers::Ok(QString("Removed translation in group '%1'").arg(QString::number(id)));
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginWeather::Api_setTranslation) {
	if(!account.IsAdmin())
		return new ApiAnswers::Error("Access denied");

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
		return new ApiAnswers::Ok(QString("Added translation in wind group '%1'").arg(QString::number(id)));
	}
	else
	{
		QStringList list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
		list << tr;
		list.removeDuplicates();
		list.sort();
		SetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, list);
		return new ApiAnswers::Ok(QString("Added translation in group '%1'").arg(QString::number(id)));
	}

}

PLUGIN_API_CALL(PluginWeather::Api_getTranslation) {
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiAnswers::Error("Access denied");

	int id = hRequest.GetArg("id").toInt();
	QString lng = hRequest.GetArg("lng");
	QString when = hRequest.GetArg("when");

	QStringList list;
	if(hRequest.HasArg("wind"))
		list = GetSettings("Wind" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();
	else
		list = GetSettings("Group" + QString::number(id) + "/" + when + "_" + lng, QStringList()).toStringList();

	return new ApiAnswers::List(list);
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_addCity) {
	Q_UNUSED(account);

	if(!hRequest.HasArg("city"))
		return new ApiAnswers::Error(QString("Missing argument 'city' for plugin Weather"));
	QString city = hRequest.GetArg("city");
	QStringList list = bunny->GetPluginSetting(GetName(), "Cities", QStringList()).toStringList();
	list.append(city);
	bunny->SetPluginSetting(GetName(), "Cities", list);
	SetSettings("Cities/" + city, hRequest.GetArg("name"));

	return new ApiAnswers::Ok(QString("Added city '%1' for bunny '%2'").arg(city, QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_removeCity)
{
	Q_UNUSED(account);

	if(!hRequest.HasArg("city"))
		return new ApiAnswers::Error(QString("Missing argument 'city' for plugin Weather"));

	QString city = hRequest.GetArg("city");
	QStringList list = bunny->GetPluginSetting(GetName(), "Cities", QStringList()).toStringList();
	list.removeAll(city);
	bunny->SetPluginSetting(GetName(), "Cities", list);

	return new ApiAnswers::Ok(QString("Removed city '%1' for bunny '%2'").arg(city, QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getCitiesList) {
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QMap<QString, QVariant> list;
	foreach(QString city, bunny->GetPluginSetting(GetName(), "Cities", QStringList()).toStringList())
	{
		list.insert(city, GetSettings("Cities/" + city, QString()));
	}
	return new ApiAnswers::MappedList(list);
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_setDefaultCity)
{
	Q_UNUSED(account);

	if(!hRequest.HasArg("city"))
		return new ApiAnswers::Error(QString("Missing argument 'city' for plugin Weather"));

	bunny->SetPluginSetting(GetName(), "Default/City", hRequest.GetArg("city"));
	return new ApiAnswers::Ok(QString("New default city defined '%1' for bunny '%2'").arg(hRequest.GetArg("city"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getDefaultCity)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "Default/City",QString()).toString());
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
		return new ApiAnswers::Ok(QString("Add webcast at '%1' to bunny '%2'").arg(hTime, QString(bunny->GetID())));
	}
	return new ApiAnswers::Error(QString("Webcast already exists at '%1' for bunny '%2'").arg(hTime, QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_RemoveWebcast)
{
	Q_UNUSED(account);

	if(!hRequest.HasArg("time"))
		return new ApiAnswers::Error(QString("Missing argument 'time' for plugin Weather"));

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap();
	QString time = hRequest.GetArg("time");
	if(list.contains(time))
	{
		list.remove(time);
		bunny->SetPluginSetting(GetName(), "Webcasts", list);

		// Recreate crons
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);
		return new ApiAnswers::Ok(QString("Remove webcast at '%1' for bunny '%2'").arg(hRequest.GetArg("time"), QString(bunny->GetID())));
	}
	return new ApiAnswers::Error(QString("No webcast at '%1' for bunny '%2'").arg(hRequest.GetArg("time"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_ListWebcast)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap());
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_AddRFID)
{
	Q_UNUSED(account);

	bunny->SetPluginSetting(GetName(), QString("RFIDWeather/%1").arg(hRequest.GetArg("tag")), hRequest.GetArg("city"));

	return new ApiAnswers::Ok(QString("Add weather for '%1' for RFID '%2', bunny '%3'").arg(hRequest.GetArg("city"), hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_RemoveRFID)
{
	Q_UNUSED(account);

	bunny->RemovePluginSetting(GetName(), QString("RFIDWeather/%1").arg(hRequest.GetArg("tag")));

	return new ApiAnswers::Ok(QString("Remove RFID '%2' for bunny '%3'").arg(hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getLang)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "Lang","fr").toString());
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_setLang)
{
	Q_UNUSED(account);
	bunny->SetPluginSetting(GetName(), "Lang",hRequest.GetArg("lg"));

	return new ApiAnswers::Ok("Lang Updated!");
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_getFrequency)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "Frequency", 0).toString());
}

PLUGIN_BUNNY_API_CALL(PluginWeather::Api_setFrequency)
{
	Q_UNUSED(account);
	bunny->SetPluginSetting(GetName(), "Frequency",hRequest.GetArg("f"));
	OnBunnyDisconnect(bunny);
	OnBunnyConnect(bunny);

	return new ApiAnswers::Ok("Frequency updated!");
}

int PluginWeather::GetWeatherFromCode(int code)
{
	return code; // GetSettings("Conditions/" + QString::number(code), 0).toInt(); // Bypass Mapping, already done in PHP cron script
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
		return sentences.at( QRandomGenerator::global()->generate() % sentences.count() );
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
		return sentences.at( QRandomGenerator::global()->generate() % sentences.count() );
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
