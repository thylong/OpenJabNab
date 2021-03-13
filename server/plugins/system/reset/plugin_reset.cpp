#include <QDateTime>
#include <QStringList>
#include <QtSql/QtSql>
#include "dbmanager.h"
#include "plugin_reset.h"
#include "ambientpacket.h"
#include "bunny.h"
#include "accountmanager.h"
#include "bunnymanager.h"
#include "cron.h"
#include "log.h"
#include "settings.h"
#include <QDate>
#include <QMap>
#include "bunny.h"
#include "messagepacket.h"
#include "packet.h"
#include "sleeppacket.h"
#include "translator.h"

PluginReset::PluginReset():PluginInterface("reset", "Reset bunnies", SystemPlugin)
{
	resettingTime.clear();
	newAccount.clear();
}

PluginReset::~PluginReset()
{
}

void PluginReset::RemoveReset()
{
	QDateTime now = QDateTime::currentDateTime();
	QMapIterator<QByteArray, QDateTime> i(resettingTime);
	while (i.hasNext())
	{
		i.next();
		if(i.value().toTime_t() + 59 < now.toTime_t())
		{
			resettingTime.remove(i.key());
			newAccount.remove(i.key());
		}
	}
}

bool PluginReset::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	if(type == PluginInterface::DoubleClick)
	{
		if(resettingTime.contains(b->GetID()))
		{
			if(b->GetGlobalSetting("OwnerAccount").toString().length() > 0)
			{
				Account * a = AccountManager::GetAccountByLogin(b->GetGlobalSetting("OwnerAccount").toByteArray());
				if(a)
				{
					a->RemoveBunny(b->GetID());
					LogInfo(QString("Remove bunny %1 from previous account (%2)").arg(QString(b->GetID()), a->GetLogin()));
				}
				b->RemoveGlobalSetting("OwnerAccount");
				LogInfo(QString("Remove owner for bunny %1").arg(QString(b->GetID())));
				if(newAccount.contains(b->GetID()))
				{
					QString login = newAccount.value(b->GetID());
					b->SetGlobalSetting("OwnerAccount", login);
					LogInfo(QString("Set new owner to bunny %1 (%2)").arg(QString(b->GetID()), login));
					a = AccountManager::GetAccountByLogin(login.toLatin1());
					if(a)
					{
						a->AddBunny(b->GetID());
						LogInfo(QString("Add bunny %1 to new account (%2)").arg(QString(b->GetID()), login));
					}
				}
				return true;
			}
		}
	}
	return false;
}

void PluginReset::InitApiCalls()
{
        DECLARE_PLUGIN_API_CALL("reset()", &PluginReset::Api_Reset);
}

PLUGIN_API_CALL(PluginReset::Api_Reset)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("bunny"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("bunny", GetName()));

	if(hRequest.GetArg("bunny").length() != 12)
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("bunny", GetName()));

	QByteArray const& bunnyID = hRequest.GetArg("bunny").toLatin1();

	Bunny * bunny = BunnyManager::GetBunny(bunnyID);
	if(action == "clean")
	{
		// Clean config
		bunny->CleanSettings();
        	return new ApiManager::ApiOk(Translator::tr("Bunny configuration is now empty", account));
	}
	else if(action == "free")
	{
		QDateTime now = QDateTime::currentDateTime();
		resettingTime.insert(bunny->GetID(), now);
		newAccount.insert(bunny->GetID(), account.GetLogin());
		LogDebug(account.GetLogin() + " need to click to free the bunny");
		QTimer::singleShot(1000 * (60 - (now.toTime_t()%60)), this, SLOT(RemoveReset()));
        	return new ApiManager::ApiOk(Translator::tr("You have one minute to double-click on the bunny button if you want to add it to your account", account));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
