#include "plugin_stats.h"
#include <QtSql/QtSql>
#include "apimanager.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "dbmanager.h"
#include "log.h"
#include "pluginmanager.h"

PluginStats::PluginStats():PluginInterface("stats", "Statistics plugin", SystemPlugin)
{
	Cron::Register(this, 15, 0, 0, NULL, Cron::Classic, QVariant());

	singleClick = GetSettings("Values/single", 0).toInt();
	doubleClick = GetSettings("Values/double", 0).toInt();
	rfid = GetSettings("Values/rfid", 0).toInt();
	ears = GetSettings("Values/ears", 0).toInt();
	voice = GetSettings("Values/voice", 0).toInt();
	record = GetSettings("Values/record", 0).toInt();
	api = GetSettings("Values/api", 0).toInt();
}

void PluginStats::AddApiCount()
{
	api++;
}

PluginStats::~PluginStats()
{
	SetSettings("Values/single", singleClick);
	SetSettings("Values/double", doubleClick);
	SetSettings("Values/rfid", rfid);
	SetSettings("Values/ears", ears);
	SetSettings("Values/voice", voice);
	SetSettings("Values/record", record);
	SetSettings("Values/api", api);
}

bool PluginStats::OnRecord(Bunny *, QString const&)
{
	record++;
	return false;
}

bool PluginStats::OnVoiceCommand(Bunny *, QString const&, QStringList const&)
{
	voice++;
	return false;
}

void PluginStats::OnCron(Bunny *, QVariant, unsigned int)
{
	// Store values and reset counters
        QSqlDatabase db = DbManager::getDb();
	bool close = DbManager::openDbIfNeeded();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("INSERT INTO stats_actions SET `date`=:date, `single`=:single, `double`=:double, `rfid`=:rfid, `ears`=:ears, `voice`=:voice, `record`=:record, `api`=:api");
	query->bindValue(":date", QDateTime::currentDateTime().toString("yyyy-MM-dd hh:mm"));
	query->bindValue(":single", singleClick);
	query->bindValue(":double", doubleClick);
	query->bindValue(":rfid", rfid);
	query->bindValue(":ears", ears);
	query->bindValue(":voice", voice);
	query->bindValue(":record", record);
	query->bindValue(":api", api);
	bool ret = query->exec();
	if(!ret)
	{
		LogError(QString("Impossible to save stats"));
	}
	singleClick = 0;
	doubleClick = 0;
	rfid = 0;
	ears = 0;
	voice = 0;
	record = 0;
	api = 0;

	SetSettings("Values/single", singleClick);
	SetSettings("Values/double", doubleClick);
	SetSettings("Values/rfid", rfid);
	SetSettings("Values/ears", ears);
	SetSettings("Values/voice", voice);
	SetSettings("Values/record", record);
	SetSettings("Values/api", api);

	delete query;
	if(close)
		DbManager::releaseDb();
}

bool PluginStats::OnRFID(Ztamp * , Bunny * )
{
	rfid++;
	return false;
}

bool PluginStats::OnEarsMove(Bunny * , int , int ) {
	ears++;
	return false;
}

bool PluginStats::OnClick(Bunny * , PluginInterface::ClickType type)
{
	if(type == PluginInterface::SingleClick)
	{
		singleClick++;
	}
	else if (type == PluginInterface::DoubleClick)
	{
		doubleClick++;
	}
	return false;
}

void PluginStats::InitApiCalls()
{
	DECLARE_PLUGIN_API_CALL("getcolors()", &PluginStats::Api_GetColors);
	DECLARE_PLUGIN_API_CALL("getplugins()", &PluginStats::Api_GetPlugins);
	DECLARE_PLUGIN_API_CALL("getbunniesip()", &PluginStats::Api_GetBunniesIP);
	DECLARE_PLUGIN_API_CALL("getbunniestimezone()", &PluginStats::Api_GetBunniesTimezone);
	DECLARE_PLUGIN_API_CALL("getbunniesname()", &PluginStats::Api_GetBunniesName);
	DECLARE_PLUGIN_API_CALL("getbunniesstatus()", &PluginStats::Api_GetBunniesStatus);
        DECLARE_PLUGIN_API_CALL("getbunniesinformation()", &PluginStats:: Api_GetBunniesInformation);
        DECLARE_PLUGIN_API_CALL("getcounters()", &PluginStats:: Api_GetCounters);
        DECLARE_PLUGIN_API_CALL("getwidgetjson()", &PluginStats:: Api_GetWidgetJson);
}

PLUGIN_API_CALL(PluginStats::Api_GetPlugins)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

	QMap<QString, QVariant> list;
	foreach(QByteArray id, listB)
	{
		Bunny * b = BunnyManager::GetBunny(id);
		QList<QString> plugins = b->GetListOfPlugins();
		foreach(QString plugin, plugins)
			list.insert(plugin, list.value(plugin).toInt() + 1);
	}

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginStats::Api_GetColors)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

	QMap<QString, QVariant> list;
	foreach(QByteArray id, listB)
	{
		Bunny * b = BunnyManager::GetBunny(id);
		QString color = b->GetPluginSetting("colorbreathing", "color", QString("violet")).toString();
		list.insert(color, list.value(color).toInt() + 1);
	}

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginStats::Api_GetBunniesIP)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

	QMap<QString, QVariant> list;
	foreach(QByteArray id, listB)
	{
		Bunny * b = BunnyManager::GetBunny(id);
		list.insert(QString(b->GetID()), b->GetGlobalSetting("LastIP"));
	}

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginStats::Api_GetBunniesTimezone)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

	QMap<QString, QVariant> list;
	foreach(QByteArray id, listB)
	{
		Bunny * b = BunnyManager::GetBunny(id);
		list.insert(QString(b->GetID()), b->GetGlobalSetting("TimeZone", "unset"));
	}

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginStats::Api_GetBunniesName)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

	QMap<QString, QVariant> list;
	foreach(QByteArray id, listB)
	{
		Bunny * b = BunnyManager::GetBunny(id);
		list.insert(QString(b->GetID()), b->GetBunnyName());
	}

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginStats::Api_GetBunniesStatus)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

	QMap<QString, QVariant> list;
	list.insert("awake", 0);
	list.insert("sleep", 0);
	foreach(QByteArray id, listB)
	{
		Bunny * b = BunnyManager::GetBunny(id);
		QString awake = b->IsSleeping() ? "sleep" : "awake";
		list.insert(awake, list.value(awake).toInt() + 1);
	}

	return new ApiAnswers::MappedList(list);
}

PLUGIN_API_CALL(PluginStats::Api_GetBunniesInformation)
{
  Q_UNUSED(account);
  Q_UNUSED(hRequest);

  QString xml = "";
  QString awake;
  QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

  QMap<QString, QVariant> list;
  foreach(QByteArray id, listB)
  {
    Bunny * b = BunnyManager::GetBunny(id);
    list.insert(QString(b->GetID()), b->GetBunnyName());
    awake = b->IsSleeping() ? "1" : "0";
    xml += "<bunny>";
    xml += "  <name>" + b->GetBunnyName() + "</name>";
    xml += "  <version>" + QString::number(b->GetVersion()) + "</version>";
    xml += "  <ID>" + QString(b->GetID()) + "</ID>";
    xml += "  <sleep>" + awake  + "</sleep>";
    xml += "  <color>" + b->GetPluginSetting("colorbreathing", "color", QString("violet")).toString() + "</color>";
    xml += "  <apiEnable>"+ b->GetGlobalSetting("VApiEnable", false).toString() + "</apiEnable>";
    xml += "  <apiPublic>" + b->GetGlobalSetting("VApiPublic", false).toString()  + "</apiPublic>";
    xml += "  <lastRecord>" +  b->GetGlobalSetting("LastRecord","").toString() + "</lastRecord>";
    xml += "  <lastLocate>" + b->GetGlobalSetting("LastLocate","").toString() + "</lastLocate>";
    xml += "  <LastCron>" + b->GetGlobalSetting("LastCron","").toString() + "</LastCron>";
    xml += "</bunny>";
  }
  return new ApiAnswers::Xml(xml);
}

PLUGIN_API_CALL(PluginStats::Api_GetWidgetJson)
{
  Q_UNUSED(account);
  Q_UNUSED(hRequest);

  int connectedBunnies = BunnyManager::Instance().GetConnectedBunnyCount();
  int ztamps = ZtampManager::Instance().GetZtampCount();
  //int plugins = PluginManager::Instance().GetPluginCount();
  int enabledPlugins = PluginManager::Instance().GetEnabledPluginCount();

  int uptime = ApiManager::getUptime();

  QString json = "{";
  json += "\"bunnies\":" + QString::number(connectedBunnies) + ",";
  json += "\"ztamps\":" + QString::number(ztamps) + ",";
  json += "\"plugins\":" + QString::number(enabledPlugins) + ",";
  json += "\"uptime\":" + QString::number(uptime);
  json += "}";
  return new ApiAnswers::Clear(json);
}

PLUGIN_API_CALL(PluginStats::Api_GetCounters)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QMap<QString, QVariant> list;
	list.insert("single", singleClick);
	list.insert("double", doubleClick);
	list.insert("rfid", rfid);
	list.insert("ears", ears);
	list.insert("voice", voice);
	list.insert("record", record);
	list.insert("api", api);

	return new ApiAnswers::MappedList(list);
}

