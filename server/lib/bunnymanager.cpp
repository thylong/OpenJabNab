#include <QtSql/QtSql>
#include "account.h"
#include "bunny.h"
#include "dbmanager.h"
#include "accountmanager.h"
#include "account.h"
#include "bunnymanager.h"
#include "plugininterface.h"
#include "pluginmanager.h"
#include "httprequest.h"
#include "translator.h"

BunnyManager::BunnyManager()
{
}

BunnyManager & BunnyManager::Instance()
{
  static BunnyManager b;
  return b;
}

void BunnyManager::LoadAllBunnies()
{
        QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("SELECT mac FROM bunny");
	query->exec();
	while(query->next())
	{
		GetBunny(query->value(0).toString().toLatin1());
	}
	delete query;
	DbManager::releaseDb();
}

QList<QByteArray> BunnyManager::GetConnectedBunniesList(void)
{
	QList<QByteArray> list;

	foreach(Bunny *b,Instance().listOfBunnies)
	{
		if(b->IsConnected()) {
			list.append(b->GetID());
		}
	}

	return list;
}

void BunnyManager::InitApiCalls()
{
	DECLARE_API_CALL("export()", &BunnyManager::Api_Export);
	DECLARE_API_CALL("getListOfConnectedBunnies()", &BunnyManager::Api_GetListOfConnectedBunnies);
	DECLARE_API_CALL("getListOfSleepingBunnies()", &BunnyManager::Api_GetListOfSleepingBunnies);
	DECLARE_API_CALL("getListOfBunnies()", &BunnyManager::Api_GetListOfBunnies);
	DECLARE_API_CALL("getListOfPluginsForBunnies()", &BunnyManager::Api_GetListOfPluginsForBunnies);
	DECLARE_API_CALL("getListOfBunniesByIP(ip)", &BunnyManager::Api_GetListOfBunniesByIP);
	DECLARE_API_CALL("removeBunny(serial)", &BunnyManager::Api_RemoveBunny);
	DECLARE_API_CALL("addBunny(serial)", &BunnyManager::Api_AddBunny);
	DECLARE_API_CALL("resetAllPassword()", &BunnyManager::Api_ResetAllPassword);
	DECLARE_API_CALL("getListofAllBunnies()",&BunnyManager::Api_GetListOfAllBunnies);
	DECLARE_API_CALL("getListofAllConnectedBunnies()",&BunnyManager::Api_GetListOfAllConnectedBunnies);
	DECLARE_API_CALL("getListofAllSleepingBunnies()",&BunnyManager::Api_GetListOfAllSleepingBunnies);
	DECLARE_API_CALL("resetAllBunniesPassword()",&BunnyManager::Api_ResetAllBunniesPassword);
	DECLARE_API_CALL("settingsForBunnies()",&BunnyManager::Api_SettingsForBunnies);
	DECLARE_API_CALL("settingsForBunny()",&BunnyManager::Api_SettingsForBunny);
	DECLARE_API_CALL("emailsForBunnies()",&BunnyManager::Api_EmailsForBunnies);
}

API_CALL(BunnyManager::Api_Export)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("options"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("options"));

	QStringList options = hRequest.GetArg("options").split(",");

	if(action == "list")
	{
		QString bunnies = "<bunnies>";
		foreach(Bunny * b, listOfBunnies)
		{
			bunnies += "<bunny mac=\"" + QString(b->GetID()) + "\"";
			if(options.contains("online"))
			{
				bunnies += " online=\"" + QString(b->IsConnected() ? "true" : "false") + QString("\"");
			}
			if(options.contains("name"))
			{
				bunnies += " name=\"" + b->GetBunnyName() + QString("\"");
			}
			if(options.contains("language"))
			{
				bunnies += " language=\"" + b->GetLanguage() + "\"";
			}
			bunnies += ">";
			if(options.contains("last"))
			{
				QStringList params;
				params << "Last JabberDisconnection" << "Last JabberConnection" << "Last PingConnection" << "LastIP" << "LastRecord" << "LastLocate" << "LastLocateString" << "LastCron" << "Last Ping";
				foreach(QString param, params)
				{
					QString key = param;
					key.replace(" ", "");
					if(key == "LastCron")
					{
						bunnies += QString("<") + key + QString("><![CDATA[") + b->GetGlobalSetting(param, QString("")).toString() + QString("]]></") + key + QString(">");
					}
					else
					{
						bunnies += QString("<") + key + QString(">") + b->GetGlobalSetting(param, QString("")).toString() + QString("</") + key + QString(">");
					}
				}
			}
			if(options.contains("owner"))
			{
				Account * a = AccountManager::GetAccountByLogin(b->GetGlobalSetting("OwnerAccount").toByteArray());
				bunnies += "<owner";
				if(a)
				{
					bunnies += " valid=\"true\"";
					if(options.contains("mail"))
					{
						bunnies += " email=\"" + a->GetEmail() + "\"";
					}
					if(options.contains("language"))
					{
						bunnies += " language=\"" + a->GetLanguage() + "\"";
					}
					if(options.contains("status"))
					{
						bunnies += " admin=\"" + QString(a->IsAdmin() ? "true" : "false") + QString("\"");
						bunnies += " premium=\"" + QString(a->IsPremium() ? "true" : "false") + QString("\"");
						bunnies += " vip=\"" + QString(a->IsVip() ? "true" : "false") + QString("\"");
					}
				}
				bunnies += ">" + b->GetGlobalSetting("OwnerAccount", QString("")).toString() + "</owner>";
			}
			if(options.contains("plugins"))
			{
				bunnies += "<plugins>";
				bunnies += QStringList(b->GetListOfPlugins()).join(",");
				bunnies += "</plugins>";
			}
			if(options.contains("boot"))
			{
				bunnies += "<boot>";
				bunnies += b->GetBootcode();
				bunnies += "</boot>";
			}
			bunnies += "</bunny>";
		}
		bunnies += "</bunnies>";
		return new ApiManager::ApiXml(bunnies);
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(BunnyManager::Api_EmailsForBunnies)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		bool connected = false;
		if(hRequest.HasArg("connected"))
			connected = (bool)hRequest.GetArg("connected").toInt();

		QMap<QString, QVariant> list;
		foreach(Bunny * b, listOfBunnies)
		{
			Account * a = AccountManager::GetAccountByLogin(b->GetGlobalSetting("OwnerAccount").toByteArray());
			QString value;
			if(a == NULL) {
				value = QString("n/a");
			} else {
				value = a->GetEmail();
				if(value == QString()) {
					value = QString("nc");
				}
			}
			if(!connected || b->IsConnected())
			{
				list.insert(b->GetID(), value);
			}
		}
		return new ApiManager::ApiMappedList(list);
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(BunnyManager::Api_SettingsForBunny)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "clone")
	{
		if(!hRequest.HasArg("plugin"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("plugin"));

		QString plugin = hRequest.GetArg("plugin");

		if(!hRequest.HasArg("from"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("from"));

		Bunny * from = GetBunny(hRequest.GetArg("from").toLatin1());
		if(from == NULL)
			return new ApiManager::ApiError(Translator::tr("Unknow bunny : %1").arg(hRequest.GetArg("from")));

		if(!hRequest.HasArg("to"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("to"));

		Bunny * to = GetBunny(hRequest.GetArg("to").toLatin1());
		if(to == NULL)
			return new ApiManager::ApiError(Translator::tr("Unknow bunny : %1").arg(hRequest.GetArg("to")));

		to->ImportPluginSettings(plugin, from->ExportPluginSettings(plugin));
		return new ApiManager::ApiOk(Translator::tr("Settings cloned for plugin '%1'").arg(plugin));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(BunnyManager::Api_SettingsForBunnies)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "get")
	{
		if(!hRequest.HasArg("key"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("key"));

		QString key = hRequest.GetArg("key");

		if(hRequest.HasArg("group") && hRequest.GetArg("group") != "")
			key = hRequest.GetArg("group") + "/" + key;

		bool filled = false;
		if(hRequest.HasArg("filled"))
			filled = (bool)hRequest.GetArg("filled").toInt();

		QMap<QString, QVariant> list;
		foreach(Bunny * b, listOfBunnies)
		{
			QVariant value;
			if(hRequest.HasArg("plugin"))
			{
				QString plugin = hRequest.GetArg("plugin");
				value = b->GetPluginSetting(plugin, key, QString("nc"));
			}
			else
			{
				value = b->GetGlobalSetting(key, QString("nc"));
			}

			if(!filled || value != "nc")
			{
				list.insert(b->GetID(), value);
			}
		}
		return new ApiManager::ApiMappedList(list);
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(BunnyManager::Api_RemoveBunny)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString serial = hRequest.GetArg("serial");
	QByteArray hexSerial = QByteArray::fromHex(serial.toLatin1());
	if(!listOfBunnies.contains(hexSerial))
		return new ApiManager::ApiError(Translator::tr("Bunny '%1' does not exist", account).arg(serial));

	Bunny * b = listOfBunnies.value(hexSerial);
/*
	QString ownerName = b->GetGlobalSetting("OwnerAccount", "").toString();
	if(ownerName != "")
	{
		Account * owner = AccountManager::GetAccountByLogin(ownerName.toLatin1());
		owner->RemoveBunny(b->GetID());
                owner->SetSaveNeeded(true);
	}
*/
        b->OnDisconnect();
        delete b;
        listOfBunnies.remove(hexSerial);

        QSqlDatabase db = DbManager::getDb();
	bool close = DbManager::openDbIfNeeded();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("DELETE FROM bunny WHERE mac=:mac");
	query->bindValue(":mac", hexSerial.toHex());
	bool ret = query->exec();
	query->finish();
	delete query;
	if(close)
		DbManager::releaseDb();
	if(ret)
		return new ApiManager::ApiOk(Translator::tr("Bunny %1 removed", account).arg(serial));
	return new ApiManager::ApiError(Translator::tr("Error when removing bunny %1", account).arg(serial));
}

int BunnyManager::GetConnectedBunnyCount(int version)
{
	return GetConnectedBunnies(version).count();
}

int BunnyManager::GetConnectedBunnyCount()
{
	return GetConnectedBunnies().count();
}

int BunnyManager::GetBunnyCount()
{
	return listOfBunnies.count();
}

Bunny * BunnyManager::GetBunny(QByteArray const& bunnyHexID)
{
	if(bunnyHexID.length() == 12)
	{
		QByteArray bunnyID = QByteArray::fromHex(bunnyHexID);

		if(listOfBunnies.contains(bunnyID))
			return listOfBunnies.value(bunnyID);

		Bunny * b = new Bunny(bunnyID);
		listOfBunnies.insert(bunnyID, b);
		return b;
	}
	return NULL;
}

Bunny * BunnyManager::GetBunny(PluginInterface * p, QByteArray const& bunnyHexID)
{
	Bunny * b = GetBunny(bunnyHexID);

	if(b)
	{
		if(p->GetType() & PluginInterface::RequiredPlugin)
			return b;
		if(p->GetType() & PluginInterface::SystemPlugin)
			return b;
		if(p->GetType() & PluginInterface::SystemAfterPlugin)
			return b;
		if(b->HasPlugin(p))
			return b;
		//if(!(p->GetType() & PluginInterface::BunnyV2Plugin))
		//	return b;
	}
	return NULL;
}

Bunny * BunnyManager::GetConnectedBunny(QByteArray const& bunnyHexID)
{
	QByteArray bunnyID = QByteArray::fromHex(bunnyHexID);

	if(listOfBunnies.contains(bunnyID))
	{
		Bunny * b = listOfBunnies.value(bunnyID);
		if(b->IsConnected())
			return b;
	}

	return NULL;
}

Bunny * BunnyManager::GetKnownBunny(QByteArray const& bunnyHexID)
{
	QByteArray bunnyID = QByteArray::fromHex(bunnyHexID);

	if(listOfBunnies.contains(bunnyID))
		return listOfBunnies.value(bunnyID);

	return NULL;
}

void BunnyManager::Close()
{
	foreach(Bunny * b, listOfBunnies)
		delete b;
	listOfBunnies.clear();
}

QVector<Bunny *> BunnyManager::GetConnectedBunnies(int version)
{
	QVector<Bunny *> list;
	foreach(Bunny * b, listOfBunnies)
		if (b->IsConnected() && b->GetVersion() == version)
			list.append(b);
	return list;
}

QVector<Bunny *> BunnyManager::GetConnectedBunnies()
{
	QVector<Bunny *> list;
	foreach(Bunny * b, listOfBunnies)
		if (b->IsConnected())
			list.append(b);
	return list;
}

void BunnyManager::PluginStateChanged(PluginInterface * p)
{
	foreach(Bunny * b, listOfBunnies)
		if (b->IsConnected())
			b->PluginStateChanged(p);
}

void BunnyManager::PluginLoaded(PluginInterface * p)
{
	foreach(Bunny * b, listOfBunnies)
		if (b->IsConnected())
			b->PluginLoaded(p);
}

void BunnyManager::PluginUnloaded(PluginInterface * p)
{
	foreach(Bunny * b, listOfBunnies)
		if (b->IsConnected())
			b->PluginUnloaded(p);
}


API_CALL(BunnyManager::Api_GetListOfConnectedBunnies)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcBunnies,Account::Read))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		if(b->IsConnected() && account.GetBunniesList().contains(b->GetID()))
			list.insert(b->GetID(), b->GetBunnyName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_GetListOfSleepingBunnies)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcBunnies,Account::Read))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		if(b->IsConnected() && b->IsSleeping() && account.GetBunniesList().contains(b->GetID()))
			list.insert(b->GetID(), b->GetBunnyName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_ResetAllPassword)
{
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	foreach(Bunny * b, listOfBunnies)
		b->ClearBunnyPassword();

	return new ApiManager::ApiOk(Translator::tr("All passwords cleared", account));
}

API_CALL(BunnyManager::Api_GetListOfPluginsForBunnies) {
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
	{
		list.insert(b->GetID(), QStringList(b->GetListOfPlugins()).join(","));
	}

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_GetListOfBunnies) {
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcBunnies,Account::Read))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		if(account.GetBunniesList().contains(b->GetID()))
			list.insert(b->GetID(), b->GetBunnyName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_GetListOfAllBunnies) {
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		list.insert(b->GetID(), b->GetBunnyName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_GetListOfBunniesByIP) {
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcBunnies,Account::Read))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		if(b->GetGlobalSetting("LastIP","0.0.0.0") == hRequest.GetArg("ip"))
			list.insert(b->GetID(), b->GetBunnyName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_GetListOfAllConnectedBunnies) {
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		if(b->IsConnected())
			list.insert(b->GetID(), b->GetBunnyName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_GetListOfAllSleepingBunnies) {
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		if(b->IsConnected() && b->IsSleeping())
			list.insert(b->GetID(), b->GetBunnyName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_ResetAllBunniesPassword) {
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Bunny * b, listOfBunnies)
		b->ClearBunnyPassword();

	return new ApiManager::ApiMappedList(list);
}

API_CALL(BunnyManager::Api_AddBunny) {
	if(!account.HasAccess(Account::AcBunnies,Account::Write))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QByteArray bunnyID = hRequest.GetArg("serial").toLatin1();
	if(listOfBunnies.contains(bunnyID))
		return new ApiManager::ApiError(Translator::tr("Bunny already exists", account));

	GetBunny(bunnyID);
	return new ApiManager::ApiOk(Translator::tr("Bunny successfully added", account));
}

QHash<QByteArray, Bunny *> BunnyManager::listOfBunnies;
