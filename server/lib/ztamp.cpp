#include <QCoreApplication>
#include <QDateTime>
#include <QtSql/QtSql>
#include "ambientpacket.h"
#include "ztamp.h"
#include "bunny.h"
#include "log.h"
#include "httprequest.h"
#include "plugininterface.h"
#include "pluginmanager.h"
#include "dbmanager.h"
#include "sleeppacket.h"
#include "xmpphandler.h"
#include "translator.h"
#include "bunnymanager.h"

Ztamp::Ztamp(QByteArray const& ztampID)
{
	needSave = false;
	id = ztampID;
	LoadConfig();
}

Ztamp::~Ztamp()
{
	SaveConfig();
}

QString Ztamp::CheckPlugin(PluginInterface * plugin, bool isAssociated)
{
	if(!plugin)
		return QString("Unknown plugin : %1");

	if(!(plugin->GetType() & PluginInterface::ZtampPlugin))
		return QString("Bad plugin type : %1");

	if(!plugin->GetEnable())
		return QString("Plugin '%1' is globally disabled");

	if(isAssociated && (!listOfPluginsPtr.contains(plugin)))
		return QString("Plugin '%1' is not associated with this ztamp");

	return QString();
}

void Ztamp::LoadConfig()
{
	QSqlDatabase db = DbManager::getDb();
	bool close = DbManager::openDbIfNeeded();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("SELECT serial, settings FROM ztamp WHERE serial=:serial");
	query->bindValue(":serial", GetID());
	query->exec();
	if(query->size() == 1)
	{
		query->first();
		QDataStream stream(query->value(1).toByteArray());
		stream.setVersion(QDataStream::Qt_4_3);
		stream >> GlobalSettings >> PluginsSettings >> listOfPlugins;
		if (stream.status() != QDataStream::Ok)
		{
			LogWarning(QString("Problem when loading settings for ztamp : %1").arg(QString(id.toHex())));
		}

		foreach(QString s, listOfPlugins)
		{
			PluginInterface * p = PluginManager::Instance().GetPluginByName(s);
			if(p)
			{
				listOfPluginsPtr.append(p);
				if(!p->GetEnable())
				{
					//LogWarning(QString("Ztamp %1 : '%2' is globally disabled !").arg(QString(GetID()), s));
				}
			}
			else
      {
//				LogError(QString("Ztamp %1 has invalid plugin (%2)!").arg(QString(GetID()), s));
      }
		}
	}
	query->finish();
	delete query;
	if(close)
		DbManager::releaseDb();
}

void Ztamp::SaveConfig()
{
	if(!needSave)
		return;
	//Log::LogDebug("Saving Ztamp " + GetZtampName());
	QByteArray settings;
	QDataStream out(&settings, QIODevice::WriteOnly);
	out.setVersion(QDataStream::Qt_4_3);
	out << GlobalSettings << PluginsSettings << listOfPlugins;// << knownRFIDTags;

	QSqlDatabase db = DbManager::getDb();
	bool close = DbManager::openDbIfNeeded();
	QStringList ownerList;
	{
		QStringList owners = GlobalSettings.contains("OwnerAccounts") ? GlobalSettings.value("OwnerAccounts").toStringList() : QStringList();
		const auto& list = owners.join(',');
		if(list.length())
		{
			QSqlQuery query(db);
			query.prepare("SELECT id FROM account WHERE `username` IN (:usernames_list)");
			query.bindValue(":usernames_list", list);
			query.exec();
			while(query.next())
				ownerList << query.value(0).toString();
		}
	}
	QSqlQuery query(db);
	query.prepare("SELECT count(id) as nb FROM ztamp WHERE `serial`=:serial");
	query.bindValue(":serial", GetID());
	if(!query.exec())
		LogError(QString("1/2 Impossible to save Ztamp in DB : %1").arg(query.lastError().driverText()));
	else
	{
		query.first();
		const auto nb = query.value(0).toInt();
		QString q("");
		if(nb == 1)
		{
			//LogDebug(QString("Updating Ztamp %1/%2 in DB").arg(QString(GetID())).arg(GetZtampName()));
			q = "UPDATE ztamp set`settings`=:settings, `server_id`=:server, `accounts`=:accounts WHERE `serial`=:serial";
		} 
		else if(nb == 0)
		{
			//LogDebug(QString("Adding new Ztamp in DB for %1/%2").arg(QString(GetID())).arg(GetZtampName()));
			q = "INSERT INTO ztamp SET `id`=NULL, `serial`=:serial, `settings`=:settings, `server_id`=:server, `accounts`=:accounts, `lastshow`=NULL";
		}
		else
			LogError(QString("Invalid number of Ztamps %2 in DB for Serial %1. Skip").arg(QString(GetID()))
																																						 .arg(nb)
							);

		if(q.length())
		{
			QSqlQuery query2(db);
			query2.prepare(q);
			query2.bindValue(":serial", GetID());
			query2.bindValue(":accounts", ownerList.join(","));
			query2.bindValue(":settings", settings);
			query2.bindValue(":server", GlobalSettings::GetInt("Database/ServerId"));
			if(!query2.exec())
			{
				LogError(QString("2/2 Impossible to save Ztamp in DB : %1").arg(query2.lastError().driverText()));
			}
			else 
				needSave = false;
		}
	}
	if(close)
		DbManager::releaseDb();
}

QMap<QString, QVariant> Ztamp::Associations()
{
	return GetGlobalSetting("Associations", QMap<QString, QVariant>()).toMap();
}

QString Ztamp::Association(Bunny * b)
{
	QMap<QString, QVariant> list = Associations();
	if(list.contains(QString(b->GetID())))
		return list.value(QString(b->GetID())).toString();
	return "";
}

bool Ztamp::Associate(Bunny * b, PluginInterface * p)
{
	return Associate(QString(b->GetID()), p->GetName());
}

bool Ztamp::Associate(Bunny * b, QString p)
{
	return Associate(QString(b->GetID()), p);
}

bool Ztamp::Associate(QString b, PluginInterface * p)
{
	return Associate(b, p->GetName());
}

bool Ztamp::Associate(QString b, QString p)
{
	QMap<QString, QVariant> list = Associations();
	if(list.contains(b))
	{
		Bunny *bunny = BunnyManager::GetBunny(b.toLatin1());
		QString plugin = list.value(b).toString();
		QMap<QString, QVariant> rfid = bunny->GetPluginSetting(plugin, "RFID", QMap<QString, QVariant>()).toMap();
		if(rfid.contains(QString(GetID())))
		{
			rfid.remove(QString(GetID()));
			bunny->SetPluginSetting(plugin, "RFID", rfid);
		}
	}
	list.insert(b, p);
	SetGlobalSetting("Associations", list);
	return true;
}

bool Ztamp::Dissociate(Bunny * b)
{
	return Dissociate(QString(b->GetID()));
}

bool Ztamp::Dissociate(QString b)
{
	QMap<QString, QVariant> list = Associations();
	list.remove(b);
	SetGlobalSetting("Associations", list);
	return true;
}

QVariant Ztamp::GetGlobalSetting(QString const& key, QVariant const& defaultValue) const
{
	if (GlobalSettings.contains(key))
		return GlobalSettings.value(key);
	else
		return defaultValue;
}

void Ztamp::SetGlobalSetting(QString const& key, QVariant const& value)
{
	needSave = true;
	GlobalSettings.insert(key, value);
}

void Ztamp::RemoveGlobalSetting(QString const& key)
{
	needSave = true;
	GlobalSettings.remove(key);
}

QVariant Ztamp::GetPluginSetting(QString const& pluginName, QString const& key, QVariant const& defaultValue) const
{
	if (PluginsSettings[pluginName].contains(key))
		return PluginsSettings[pluginName].value(key);
	else
		return defaultValue;
}

void Ztamp::SetPluginSetting(QString const& pluginName, QString const& key, QVariant const& value)
{
	needSave = true;
	PluginsSettings[pluginName].insert(key, value);
}

void Ztamp::RemovePluginSetting(QString const& pluginName, QString const& key)
{
	needSave = true;
	PluginsSettings[pluginName].remove(key);
}

// API Add plugin to this ztamp
void Ztamp::AddPlugin(PluginInterface * p)
{
	if(!listOfPlugins.contains(p->GetName()))
	{
		listOfPlugins.append(p->GetName());
		listOfPluginsPtr.append(p);
		needSave = true;
		p->OnZtampConnect(this);
		SaveConfig();
	}
}

// API Remove plugin to this ztamp
void Ztamp::RemovePlugin(PluginInterface * p)
{
	if(listOfPlugins.contains(p->GetName()))
	{
		listOfPlugins.removeAll(p->GetName());
		listOfPluginsPtr.removeAll(p);
		needSave = true;
		p->OnZtampDisconnect(this);
		SaveConfig();
	}
}

// Global plugin enable/disable
void Ztamp::PluginStateChanged(PluginInterface * p)
{
	if(listOfPluginsPtr.contains(p))
	{
		if(p->GetEnable())
			p->OnZtampConnect(this);
		else
			p->OnZtampDisconnect(this);
	}
}

// New plugin loaded
void Ztamp::PluginLoaded(PluginInterface * p)
{
	if(listOfPlugins.contains(p->GetName()))
	{
		listOfPluginsPtr.append(p);
		if(p->GetEnable())
			p->OnZtampConnect(this);
	}
}

// Plogin unloaded
void Ztamp::PluginUnloaded(PluginInterface * p)
{
	if(listOfPluginsPtr.contains(p))
	{
		listOfPluginsPtr.removeAll(p);
		if(p->GetEnable())
			p->OnZtampDisconnect(this);
	}
}

// Ztamp is connected
void Ztamp::OnConnect()
{
	// Send to all 'system' plugins
	PluginManager::Instance().OnZtampConnect(this);

	// And all ztamp's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->OnZtampConnect(this);
	}
}

// Ztamp is gone away
void Ztamp::OnDisconnect()
{
	// Send to all 'system' plugins
	PluginManager::Instance().OnZtampDisconnect(this);

	// And all ztamp's plugins
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
			p->OnZtampDisconnect(this);
	}
	SaveConfig();
}

// Called when a RFID Tad was read
bool Ztamp::OnRFID(Bunny * bunny)
{
	if(PluginManager::Instance().OnRFID(this, bunny))
		return true;

	// Call OnClick for all 'system' plugins until one returns true
	foreach(PluginInterface * p, listOfPluginsPtr)
	{
		if(p->GetEnable())
		{
			if(p->OnRFID(this, bunny))
				return true;
		}
	}
	return false;
}

/*******/
/* API */
/*******/

void Ztamp::InitApiCalls()
{
	DECLARE_API_CALL("registerPlugin(name)", &Ztamp::Api_AddPlugin);
	DECLARE_API_CALL("unregisterPlugin(name)", &Ztamp::Api_RemovePlugin);
	DECLARE_API_CALL("getListOfActivePlugins()", &Ztamp::Api_GetListOfAssociatedPlugins);
	DECLARE_API_CALL("setZtampName(name)", &Ztamp::Api_SetZtampName);
	DECLARE_API_CALL("removeOwner(login)", &Ztamp::Api_RemoveOwner);
	DECLARE_API_CALL("resetOwner()", &Ztamp::Api_ResetOwner);

	DECLARE_API_CALL("owner()", &Ztamp::Api_Owner);
	DECLARE_API_CALL("config()", &Ztamp::Api_Config);
	DECLARE_API_CALL("plugin()", &Ztamp::Api_Plugin);
}

API_CALL(Ztamp::Api_Config)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "name")
	{
		if(hRequest.HasArg("set"))
		{
			QString name = hRequest.GetArg("set");
			SetZtampName( name );
			return new ApiAnswers::Ok(Translator::tr("Ztamp '%1' is now named '%2'", account).arg(GetID(), name));
		}
		return new ApiAnswers::String( GetZtampName() );
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(Ztamp::Api_Plugin)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "association")
	{
		return new ApiAnswers::MappedList(GetGlobalSetting("Associations", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "deassociate")
	{
		if(!hRequest.HasArg("bunny"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("bunny"));

		QString bunny = hRequest.GetArg("bunny");
		Dissociate(bunny);

		return new ApiAnswers::Ok(Translator::tr("Removed association for bunny '%1'", account).arg(bunny));
	}
	else if(action == "register")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("name"));

		QString name = hRequest.GetArg("name");

		PluginInterface * plugin = PluginManager::Instance().GetPluginByName(name);

		QString error = CheckPlugin(plugin);
		if(!error.isNull())
			return new ApiAnswers::Error(error.arg(name));

		AddPlugin(plugin);
		return new ApiAnswers::Ok(Translator::tr("Added '%1' as active plugin", account).arg(plugin->GetVisualName()));
	}
	else if(action == "unregister")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("name"));

		QString name = hRequest.GetArg("name");

		PluginInterface * plugin = PluginManager::Instance().GetPluginByName(name);
		QString error = CheckPlugin(plugin);
		if(!error.isNull())
			return new ApiAnswers::Error(error.arg(name));

		RemovePlugin(plugin);
		return new ApiAnswers::Ok(Translator::tr("Removed '%1' as active plugin", account).arg(plugin->GetVisualName()));
	}
	else if(action == "active")
	{
		QList<QString> list;
		foreach (PluginInterface * p, listOfPluginsPtr)
			list.append(p->GetName());

		return new ApiAnswers::List(list);
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(Ztamp::Api_Owner)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::List(GetGlobalSetting("OwnerAccounts",QStringList()).toStringList());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("login"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("login"));

		QString owner = hRequest.GetArg("login");
		if(owner == "")
			return new ApiAnswers::Error(Translator::tr("Bad login", account));

		QStringList owners = GetGlobalSetting("OwnerAccounts",QStringList()).toStringList();
		if(owners.contains(owner))
			return new ApiAnswers::Error(Translator::tr("'%1' is not an owner", account).arg(owner));

		owners.append(owner);
		SetGlobalSetting("OwnerAccounts", owners);
		return new ApiAnswers::Ok(Translator::tr("Owner '%1' added", account).arg(owner));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("login"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("login"));

		QString owner = hRequest.GetArg("login");
		if(owner == "")
			return new ApiAnswers::Error(Translator::tr("Bad login", account));

		QStringList owners = GetGlobalSetting("OwnerAccounts",QStringList()).toStringList();
		if(owners.contains(owner))
		{
			owners.removeAll(owner);
			SetGlobalSetting("OwnerAccounts", owners);
			return new ApiAnswers::Ok(Translator::tr("Owner '%1' removed", account).arg(owner));
		}
		return new ApiAnswers::Error(Translator::tr("'%1' is not an owner", account).arg(owner));
	}
	else if(action == "reset")
	{
		RemoveGlobalSetting("OwnerAccounts");
		return new ApiAnswers::Ok(Translator::tr("All owners removed", account));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(Ztamp::Api_AddPlugin)
{
	Q_UNUSED(account);

	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(hRequest.GetArg("name"));

	QString error = CheckPlugin(plugin);
	if(!error.isNull())
		return new ApiAnswers::Error(error.arg(hRequest.GetArg("name")));

	AddPlugin(plugin);
	return new ApiAnswers::Ok(Translator::tr("Added '%1' as active plugin", account).arg(plugin->GetVisualName()));
}

API_CALL(Ztamp::Api_RemovePlugin)
{
	Q_UNUSED(account);

	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(hRequest.GetArg("name"));
	QString error = CheckPlugin(plugin);
	if(!error.isNull())
		return new ApiAnswers::Error(error.arg(hRequest.GetArg("name")));

	RemovePlugin(plugin);
	return new ApiAnswers::Ok(Translator::tr("Removed '%1' as active plugin", account).arg(plugin->GetVisualName()));
}

API_CALL(Ztamp::Api_GetListOfAssociatedPlugins)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QList<QString> list;
	foreach (PluginInterface * p, listOfPluginsPtr)
		list.append(p->GetName());

	return new ApiAnswers::List(list);

}

API_CALL(Ztamp::Api_SetZtampName)
{
	SetZtampName( hRequest.GetArg("name") );

	return new ApiAnswers::Ok(Translator::tr("Ztamp '%1' is now named '%2'", account).arg(GetID(), hRequest.GetArg("name")));
}

API_CALL(Ztamp::Api_RemoveOwner)
{
	QString owner = hRequest.GetArg("login");
	if(owner == "")
		return new ApiAnswers::Error(Translator::tr("Bad login", account));

	QStringList owners = GetGlobalSetting("OwnerAccounts","").toStringList();
	owners.removeAll(owner);
	SetGlobalSetting("OwnerAccounts", owners);
	return new ApiAnswers::Ok(Translator::tr("Owner '%1' removed", account).arg(owner));
}

API_CALL(Ztamp::Api_ResetOwner)
{
	Q_UNUSED(hRequest);

	RemoveGlobalSetting("OwnerAccounts");
	return new ApiAnswers::Ok(Translator::tr("Owner cleared", account));
}
