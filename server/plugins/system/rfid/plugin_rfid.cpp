#include <QDateTime>
#include <QStringList>
#include "plugin_rfid.h"
#include "account.h"
#include "accountmanager.h"
#include "ambientpacket.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "ztamp.h"
#include "ztampmanager.h"
#include "log.h"
#include "settings.h"
#include "translator.cpp" // meh ?
#include "dbmanager.h"

PluginRFID::PluginRFID():PluginInterface("rfid", "Manage RFID requests", SystemPlugin) {}

bool PluginRFID::HttpRequestHandle(HTTPRequest & request)
{
	QString uri = request.GetURI();
	if (uri.startsWith("/vl/rfid.jsp"))
	{
		QString serialnumber = request.GetArg("sn");
		if(serialnumber.length() == 12)
		{
			QString tagId = request.GetArg("t");
			if(tagId.length() > 6)
			{
				SetSettings("global/LastTag", tagId);

				Ztamp * z = ZtampManager::GetZtamp(this, tagId.toLatin1());
        // Update lastshow for Ztamp
        QSqlDatabase db = DbManager::getOpenDb();
        QSqlQuery *query = new QSqlQuery(db);
        query->prepare("UPDATE ztamp set lasthow=NOW() WHERE serial=:serial");
        query->bindValue(":serial", tagId);
        query->exec();
        delete query;
        DbManager::releaseDb();

				Bunny * b = BunnyManager::GetBunny(this, serialnumber.toLatin1());
				b->SetPluginSetting(GetName(), "LastTag", tagId);
				/* Get Owner of the bunny */
				QString Bac = b->GetGlobalSetting("OwnerAccount","").toString();
				if(Bac != "")
				{
					/* Get Owners of the Ztamp */
					QStringList Zac = z->GetGlobalSetting("OwnerAccounts","").toStringList();
					if(!Zac.contains(Bac))
					{
						LogWarning(QString("Ztamp: %1 added to account %2 by bunny %3").arg(tagId,Bac,serialnumber));
					}
					Account *Ac = AccountManager::GetAccountByLogin(Bac.toLatin1());
					if(Ac != NULL)
					{
						Ac->AddZtamp(tagId.toLatin1());
					}
					Zac.append(Bac);
					Zac.removeDuplicates();
					Zac.sort();
					z->SetGlobalSetting("OwnerAccounts",Zac);
				}

				if (z->OnRFID(b))
					return true;
				if (b->OnRFID(QByteArray::fromHex(tagId.toLatin1())))
					return true;
			}
		}
	}
	return false;
}

void PluginRFID::OnInitPacket(const Bunny * bunny, AmbientPacket & a, SleepPacket &)
{
	int rfid = bunny->GetPluginSetting(GetName(), "disable", 0).toInt();
	if(rfid)
		LogWarning(QString("Disable RFID for bunny %1 %2").arg(bunny->GetBunnyName(),QString(bunny->GetID())));
	a.SetServiceValue(AmbientPacket::Service_DisableRfid, rfid);
}

/*******/
/* API */
/*******/
void PluginRFID::InitApiCalls()
{
	DECLARE_PLUGIN_API_CALL("getLastTag()", PluginRFID, Api_GetLastTag);
	DECLARE_PLUGIN_API_CALL("getLastTagForBunny(sn)", PluginRFID, Api_GetLastTagForBunny);
	DECLARE_PLUGIN_BUNNY_API_CALL("getLastBunnyTag()", PluginRFID, Api_GetLastBunnyTag);
        DECLARE_PLUGIN_BUNNY_API_CALL("config()", PluginRFID, Api_Config);
}

PLUGIN_API_CALL(PluginRFID::Api_GetLastTag)
{
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	return new ApiManager::ApiString(GetSettings("global/LastTag", QString()).toString());
}

PLUGIN_BUNNY_API_CALL(PluginRFID::Api_GetLastBunnyTag)
{
	Q_UNUSED(hRequest);
	Q_UNUSED(account);

	return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "LastTag", QString()).toString());
}

PLUGIN_API_CALL(PluginRFID::Api_GetLastTagForBunny)
{
	Bunny * b = BunnyManager::GetBunny(this, hRequest.GetArg("sn").toLatin1());
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied");

	return new ApiManager::ApiString(b->GetPluginSetting(GetName(), "LastTag", QString()).toString());
}

PLUGIN_BUNNY_API_CALL(PluginRFID::Api_Config)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "disable")
	{
		if(!hRequest.HasArg("subaction"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("subaction", GetName()));

		QString subaction = hRequest.GetArg("subaction");

		if(subaction == "get")
		{
			int rfid = bunny->GetPluginSetting(GetName(), "disable", 0).toInt();
			return new ApiManager::ApiString(QString::number(rfid));
		}
		else if(subaction == "set")
		{
			if(!hRequest.HasArg("value"))
				return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("value", GetName()));

			int value = hRequest.GetArg("value").toInt();
			if(value != 0)
			{
				value = 1;
			}
			bunny->SetPluginSetting(GetName(), "disable", value);
			QString status = value == 1 ? Translator::tr("enabled") : Translator::tr("disabled");
			return new ApiManager::ApiOk(Translator::tr("Setting '%1' is now %2 for bunny %3", account).arg(Translator::tr("RFID Disabled", account), status, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("subaction", GetName()));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

