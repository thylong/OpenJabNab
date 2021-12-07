#include <QDateTime>
#include <QStringList>

#include "bunny.h"
#include "bunnymanager.h"
#include "dbmanager.h"
#include "log.h"
#include "settings.h"
#include "translator.h"

#include "plugin_locate.h"

PluginLocate::PluginLocate()
	: PluginInterface("locate", "Manage Locate requests", RequiredPlugin)
{
	bool changed = false;
	QStringList bad = GetSettings("Server/List", QStringList()).toStringList();
	foreach(QString serialnumber, bad)
	{
		QString server = GetSettings(QString("Server/%1").arg(serialnumber), QString()).toString();
		if(server == QString())
		{
			changed = true;
			bad.removeAll(serialnumber);
			RemoveSettings(QString("Server/%1").arg(serialnumber), false);
		}
	}
	if(changed)
	{
		SetSettings("Server/List", bad);
	}
	customList << "PingServer" << "BroadServer" << "XmppServer" << "ListeningXmppPort" << "ListeningXmppAltPort" << "XmppTcpIdleTime"  << "XmppVioletPlatformComponent" << "XmppVioletObjectsComponent" << "VioletAppletComponent" << "XmppVioletPlatformClient";
	configList << "wifi_ssid" << "wifi_auth" << "wifi_crypt" << "wifi_key" << "server_url" << "dhcp" << "ip" << "mask" << "gateway" << "dns_server";
}


const QHash<QString, QString> PluginLocate::GetChangelog(void)
{
	QHash<QString, QString> revisions;
	revisions.insert("1.2.1", "Save bootcode version in bunny");
	revisions.insert("1.3.0", "Get platform for bad configured bunnies");
	revisions.insert("1.3.1", "Better API");
	revisions.insert("1.3.2", "Add API to change bunny config");
	revisions.insert("1.4.0", "Add function to remotely reconfigure bunny");
	revisions.insert("1.5.0", "Add new fields in locate string");
	revisions.insert("1.6.0", "Add relocate feature, and list of bunnies");
	revisions.insert("1.6.1", "Add feature to change wifi setup");
	return revisions;
}

void PluginLocate::OnBunnyConnect(Bunny * b)
{
	waitingBunnies.removeAll(QString(b->GetID()));
	failingBunnies.removeAll(QString(b->GetID()));
	//bunny->SetGlobalSetting("ConnectTime", QDateTime::currentDateTime());
  QSqlDatabase db = DbManager::getOpenDb();
  QSqlQuery *query = new QSqlQuery(db);
  query->prepare("UPDATE bunny SET lastlocate=NOW() WHERE mac=:mac");
  query->bindValue(":mac",b->GetID());
  query->exec();
  delete query;
  DbManager::releaseDb();
}

bool PluginLocate::HttpRequestHandle(HTTPRequest & request)
{
	QString uri = request.GetURI();
	if (uri.contains("/locate.jsp"))
	{
		QString serialnumber = request.GetArg("sn").remove(':');
		if(waitingBunnies.contains(serialnumber))
		{
			failingBunnies << serialnumber;
			failingBunnies.removeDuplicates();
		}
		else
		{
			waitingBunnies << serialnumber;
			waitingBunnies.removeDuplicates();
		}
		Bunny * bunny = BunnyManager::GetBunny(this, serialnumber.toLatin1());
		if(!bunny)
		{
			LogError(QString("Can't load bunny %1. Abort").arg(serialnumber));
			return false;
		}

		bunny->SetBootcode(request.GetArg("v"));
		if(request.HasArg("c"))
		{
			bunny->SetPluginSetting(GetName(), "BunnyConfiguration", request.GetArg("c"));
		}

		QString pingServer = bunny->GetPluginSetting(GetName(), "PingServer", GlobalSettings::GetString("OpenJabNabServers/PingServer")).toString();
		QString broadServer = bunny->GetPluginSetting(GetName(), "BroadServer", GlobalSettings::GetString("OpenJabNabServers/BroadServer")).toString();
		QString xmppServer = bunny->GetPluginSetting(GetName(), "XmppServer", GlobalSettings::GetString("OpenJabNabServers/XmppServer")).toString();
		QString xmppPort = bunny->GetPluginSetting(GetName(), "ListeningXmppPort", GlobalSettings::GetString("OpenJabNabServers/ListeningXmppPort")).toString();
		QString xmppAltPort = bunny->GetPluginSetting(GetName(), "ListeningXmppAltPort", GlobalSettings::GetString("OpenJabNabServers/ListeningXmppAltPort")).toString();
		QString xmppTcpIdleTime = bunny->GetPluginSetting(GetName(), "XmppTcpIdleTime", GlobalSettings::GetString("OpenJabNabServers/XmppTcpIdleTime")).toString();

		QString XmppVioletPlatformComponent = bunny->GetPluginSetting(GetName(), "XmppVioletPlatformComponent", GlobalSettings::GetString("OpenJabNabServers/XmppVioletPlatformComponent")).toString();
		QString XmppVioletObjectsComponent = bunny->GetPluginSetting(GetName(), "XmppVioletObjectsComponent", GlobalSettings::GetString("OpenJabNabServers/XmppVioletObjectsComponent")).toString();
		QString XmppVioletAppletComponent = bunny->GetPluginSetting(GetName(), "XmppVioletAppletComponent", GlobalSettings::GetString("OpenJabNabServers/XmppVioletAppletComponent")).toString();
		QString XmppVioletPlatformClient = bunny->GetPluginSetting(GetName(), "XmppVioletPlatformClient", GlobalSettings::GetString("OpenJabNabServers/XmppVioletPlatformClient")).toString();

/*
		QString pingServer = GlobalSettings::GetString("OpenJabNabServers/PingServer");
		QString broadServer = GlobalSettings::GetString("OpenJabNabServers/BroadServer");
		QString xmppServer = GlobalSettings::GetString("OpenJabNabServers/XmppServer");
		QString xmppPort = GlobalSettings::GetString("OpenJabNabServers/ListeningXmppPort");
*/

		if(request.HasArg("r"))
		{
			if(request.GetArg("r") == "1")
			{
				//LogInfo(QString("Requesting a restart LOCATE for tag %1").arg(serialnumber));
				LogBoot("Restart", serialnumber.toLatin1());
			}
			else
			{
				//LogInfo(QString("Requesting a full reboot LOCATE for tag %1").arg(serialnumber));
				LogBoot("Reboot", serialnumber.toLatin1());
			}
		}
		else
		{
			LogBoot("Boot", serialnumber.toLatin1());
			//LogInfo(QString("Requesting LOCATE for tag %1").arg(serialnumber));
		}

		QString locateString;
		locateString += QString("ping %1\n").arg(pingServer);
		locateString += QString("broad %1\n").arg(broadServer);
		locateString += QString("xmpp_domain %1:%2\n").arg(xmppServer, xmppPort);
		locateString += QString("xmpp_alt %1:%2\n").arg(xmppServer, xmppAltPort);
		locateString += QString("xmpp_timeout %1\n").arg(xmppTcpIdleTime);
		locateString += QString("date %1\n").arg(QString::number(QDateTime::currentDateTime().toTime_t()));

		if(GetSettings("Fields/Special", false).toBool() || bunny->GetPluginSetting(GetName(), "Fields/Special", false).toBool())
		{
			locateString += QString("platform %1\n").arg(XmppVioletPlatformComponent);
			locateString += QString("objects %1\n").arg(XmppVioletObjectsComponent);
			locateString += QString("applet %1\n").arg(XmppVioletAppletComponent);
			locateString += QString("client %1\n").arg(XmppVioletPlatformClient);
		}
		request.reply = locateString.toLatin1();

		if(bunny)
		{
			bunny->SetGlobalSetting("LastLocate", QDateTime::currentDateTime());
			bunny->SetGlobalSetting("LastLocateString", locateString);
		}

		return true;
	}
	//else if (uri.startsWith("/vl/conf.jsp"))
	else if (uri.contains("/conf.jsp"))
	{
		QString serialnumber = request.GetArg("sn").remove(':');
		Bunny * bunny = BunnyManager::GetBunny(this, serialnumber.toLatin1());
		bunny->SetBootcode(request.GetArg("v"));

		LogDebug(QString("Bunny %1 ask a reconfiguration").arg(serialnumber));
		QString locateString;

		foreach(QString configString, configList)
		{
			QString configValue = bunny->GetPluginSetting(GetName(), configString, QString()).toString();
			if(configValue != "")
			{
				locateString += QString("%1 %2\n").arg(configString, configValue);
			}
		}

		request.reply = locateString.toLatin1();

		return true;
	}
	//else if (uri.startsWith("/vl/relocate.jsp"))
	else if (uri.contains("/relocate.jsp"))
	{
		QString serialnumber = request.GetArg("sn").remove(':');
		QString server = request.GetArg("s");
		Bunny * bunny = BunnyManager::GetBunny(this, serialnumber.toLatin1());
		if(bunny)
		{
			bunny->SetBootcode(request.GetArg("v"));
		}
		if(server != QString())
		{
			QStringList bad = GetSettings("Server/List", QStringList()).toStringList();
			bad.append(serialnumber);
			bad.removeDuplicates();
			SetSettings(QString("Server/%1").arg(serialnumber), server);
			SetSettings("Server/List", bad);
		}
		request.reply = QString::number(bunny->GetPluginSetting(GetName(), "Relocate", 1).toInt()).toLatin1();
		return true;
	}
	else
		return false;
}

void PluginLocate::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("setcustomlocate(param,value)", &PluginLocate::Api_SetCustomLocateSetting);
	DECLARE_PLUGIN_BUNNY_API_CALL("getcustomlocate(param)", &PluginLocate::Api_GetCustomLocateSetting);
	DECLARE_PLUGIN_BUNNY_API_CALL("server()", &PluginLocate::Api_BunnyServer);
	DECLARE_PLUGIN_BUNNY_API_CALL("config()", &PluginLocate::Api_BunnyConfig);
	DECLARE_PLUGIN_BUNNY_API_CALL("custom()", &PluginLocate::Api_BunnyCustom);
	DECLARE_PLUGIN_API_CALL("server()", &PluginLocate::Api_Server);
}

PLUGIN_BUNNY_API_CALL(PluginLocate::Api_BunnyConfig)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "set")
	{
		if(!hRequest.HasArg("config"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("config", GetName()));

		QString config = hRequest.GetArg("config");

		if(configList.contains(config))
		{
			if(!hRequest.HasArg("value"))
				return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("value", GetName()));

			QString value = hRequest.GetArg("value");

			if(value == "")
			{
				bunny->RemovePluginSetting(GetName(), config);
				return new ApiAnswers::Ok(Translator::tr("Config removed for bunny '%1'", account).arg(QString(bunny->GetID())));
			}
			else
			{
				bunny->SetPluginSetting(GetName(), config, value);
				return new ApiAnswers::Ok(Translator::tr("Config updated for bunny '%1'", account).arg(QString(bunny->GetID())));
			}
		}
		return new ApiAnswers::Error(Translator::tr("Bad value '%1' for argument '%2'", account).arg(config, "config"));
	}
	else if(action == "get")
	{
		if(!hRequest.HasArg("config"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("config", GetName()));

		QString config = hRequest.GetArg("config");

		if(configList.contains(config))
		{
			QString value = bunny->GetPluginSetting(GetName(), config, QString()).toString();
			return new ApiAnswers::String(value);
		}
		return new ApiAnswers::Error(Translator::tr("Bad value '%1' for argument '%2'", account).arg(config, "config"));
	}
	else if(action == "relocate")
	{
		if(!hRequest.HasArg("value"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("calue", GetName()));

		int value = hRequest.GetArg("value").toInt();

		bunny->SetPluginSetting(GetName(), "Relocate", value);
		return new ApiAnswers::Ok(Translator::tr("Config updated for bunny '%1'", account).arg(QString(bunny->GetID())));
	}
	else if(action == "getraw")
	{
		return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "BunnyConfiguration", QString()).toString());
	}
	else if(action == "getconfig")
	{
		QString configs = "<configs>";
		foreach (QString configString, configList)
		{
			configs += "<config ";
			configs += "name='" + configString + "'>";
			configs += bunny->GetPluginSetting(GetName(), configString, QString()).toString();
			configs += "</config>";
		}
		configs += "</configs>";
		return new ApiAnswers::Xml(configs);
	}
	else if(action == "update")
	{
		QString data = QString("<iq type='set' to='%1@%2/%3' from='%2@%2/server' id='exec1'><command xmlns='http://jabber.org/protocol/commands' node='reconfigure' action='execute'/></iq>").arg(QString(bunny->GetID()), GlobalSettings::GetString("OpenJabNabServers/XmppServer"), QString(bunny->GetXmppResource()));
		bunny->SendExpertData(data.toLatin1());
		return new ApiAnswers::Ok(Translator::tr("Setup changed for bunny '%1'", account).arg(QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginLocate::Api_BunnyCustom)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "set")
	{
		foreach(QString custom, customList)
		{
			if(hRequest.HasArg(custom))
			{
				QString value = hRequest.GetArg(custom);
				if(value != "")
				{
					bunny->SetPluginSetting(GetName(), custom, value);
				}
				else
				{
					bunny->RemovePluginSetting(GetName(), custom);
				}
			}
		}
		return new ApiAnswers::Ok(QString("Custom settings updated"));
	}
	else if(action == "get")
	{
		QString customs = "<customs>";
		foreach (QString customString, customList)
		{
			customs += "<custom ";
			customs += "name='" + customString + "'>";
			customs += bunny->GetPluginSetting(GetName(), customString, QString()).toString();
			customs += "</custom>";
		}
		customs += "</customs>";
		return new ApiAnswers::Xml(customs);
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginLocate::Api_BunnyServer)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "change")
	{
		QStringList bad = GetSettings("Server/List", QStringList()).toStringList();
		bad.removeAll(QString(bunny->GetID()));
		RemoveSettings(QString("Server/%1").arg(QString(bunny->GetID())), false);
		SetSettings("Server/List", bad);
		QString data = QString("<iq type='set' to='%1@%2/%3' from='%2@%2/server' id='exec1'><command xmlns='http://jabber.org/protocol/commands' node='updateconfig' action='execute'/></iq>").arg(QString(bunny->GetID()), GlobalSettings::GetString("OpenJabNabServers/XmppServer"), QString(bunny->GetXmppResource()));
		bunny->SendExpertData(data.toLatin1());
		return new ApiAnswers::Ok(Translator::tr("Setup changed for bunny '%1'", account).arg(QString(bunny->GetID())));
	}
	else if(action == "get")
	{
		return new ApiAnswers::String(GetSettings(QString("Server/%1").arg(QString(bunny->GetID())), QString()).toString());
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginLocate::Api_Server)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> list;
		foreach(QString serialnumber, GetSettings("Server/List", QStringList()).toStringList())
		{
			list.insert(serialnumber, GetSettings(QString("Server/%1").arg(serialnumber), QString()).toString());
		}
		return new ApiAnswers::MappedList(list);
	}
	else if(action == "waiting")
	{
		if(hRequest.HasArg("delay"))
		{
			QMap<QString, QVariant> list;
			foreach(QString serialnumber, waitingBunnies)
			{
				if(auto* bunny = BunnyManager::GetBunny(this, serialnumber.toLatin1()))
				  list.insert(serialnumber, QString::number(bunny->GetGlobalSetting("LastLocate", QDateTime::currentDateTime()).toDateTime().secsTo(QDateTime::currentDateTime())));
			}
			return new ApiAnswers::MappedList(list);
		}
		else
		{
			return new ApiAnswers::List(waitingBunnies);
		}
	}
	else if(action == "failing")
	{
		return new ApiAnswers::List(failingBunnies);
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginLocate::Api_SetCustomLocateSetting)
{
	Q_UNUSED(account);

	QString hParam = hRequest.GetArg("param");
	if(customList.contains(hParam))
	{
		if(hRequest.GetArg("value") != "")
		{
			bunny->SetPluginSetting(GetName(), hParam, hRequest.GetArg("value"));
			return new ApiAnswers::Ok(QString("Setting '%1' to custom value '%2'").arg(hParam, hRequest.GetArg("value")));
		}
		else
		{
			bunny->RemovePluginSetting(GetName(), hParam);
			return new ApiAnswers::Ok(QString("Removing '%1' custom value").arg(hParam));
		}
	}
	return new ApiAnswers::Error(QString("'%1' is not a setting for this plugin").arg(hParam));
}

PLUGIN_BUNNY_API_CALL(PluginLocate::Api_GetCustomLocateSetting)
{
	Q_UNUSED(account);

	QString hParam = hRequest.GetArg("param");
	if(customList.contains(hParam))
	{
		return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), hParam, QString("")).toString());
	}
	return new ApiAnswers::Error(QString("'%1' is not a setting for this plugin").arg(hParam));
}
