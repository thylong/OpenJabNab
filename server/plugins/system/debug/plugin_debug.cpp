#include <QDateTime>
#include <QStringList>
#include <QtSql/QtSql>
#include "dbmanager.h"
#include "plugin_debug.h"
#include "ambientpacket.h"
#include "bunny.h"
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

PluginDebug::PluginDebug():PluginInterface("debug", "Help debug", SystemPlugin)
{
}

PluginDebug::~PluginDebug()
{
}

bool PluginDebug::XmppBunnyMessage(Bunny * b, QByteArray const& data)
{
	QRegExp rx("<message[^>]*>(.*)</message>");
	rx.setMinimal(true);
	int pos = 0;
	if (rx.indexIn(data) != -1)
	{
		bool used = false;
		while (rx.indexIn(data, pos) != -1)
		{
			pos = rx.indexIn(data, pos) + rx.matchedLength();
			QString message = rx.cap(1);

			rx.setPattern("<debug xmlns=\"OJN:nabaztag:debug:([^\"]+)\"");
			if (rx.indexIn(message) != -1)
			{
				used = true;
				QString caller = rx.cap(1);
				QString info;
				QString dump;
				QRegExp rx("<info>(.+)</info>");
				if (rx.indexIn(message) != -1)
				{
					info = rx.cap(1);
				}
				rx.setPattern("<dump>(.+)</dump>");
				if (rx.indexIn(message) != -1)
				{
					dump = rx.cap(1);
				}
				if(GetSettings("Save/" + caller, 0).toInt() != 0)
				{
					used = true;
					QSqlDatabase db = DbManager::getDb();
					bool close = DbManager::openDbIfNeeded();
					QSqlQuery *query = new QSqlQuery(db);

					query->prepare("INSERT INTO debug SET `date`=:date, `mac`=:mac, `type`=:type, `info`=:info, `dump`=:dump");
					query->bindValue(":date", QDateTime::currentDateTime().toString("yyyy-MM-dd hh:mm:ss"));
					query->bindValue(":mac", QString(b->GetID()));
					query->bindValue(":type", caller);
					query->bindValue(":info", info);
					query->bindValue(":dump", dump);

					if(!query->exec())
					{
						LogError("Can't save debug values");
					}
					delete query;
					if(close)
					{
						DbManager::releaseDb();
					}
				}
				if(GetSettings("Display/" + caller, 0).toInt() != 0)
				{
					used = true;
					LogInfo(QString("Debug for %1 (%2)").arg(caller, QString(b->GetID())));
					LogInfo(QString("|-- %1=%2").arg("info", info));
					LogInfo(QString("|-- %1=%2").arg("dump", dump));
				}
			}
			pos++;
		}
		return used;
	}
	return false;
}

void PluginDebug::InitApiCalls()
{
        DECLARE_PLUGIN_BUNNY_API_CALL("info()", PluginDebug, Api_Info);
        DECLARE_PLUGIN_API_CALL("config()", PluginDebug, Api_Config);
}

PLUGIN_BUNNY_API_CALL(PluginDebug::Api_Info)
{
	Q_UNUSED(bunny);
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "silent")
	{
        	return new ApiManager::ApiOk(Translator::tr("'%1' debug requested, waiting answer", account).arg(Translator::tr("silent", account)));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginDebug::Api_Config)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "save")
	{
		if(!hRequest.HasArg("save"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("save", GetName()));

		QString save = hRequest.GetArg("save");

		if(hRequest.HasArg("value"))
		{
			QString value = hRequest.GetArg("value");
			SetSettings("Save/" + save, value);
			if(value == "1")
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' debug will be saved in database", account).arg(Translator::tr(save, account)));
			}
			else
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' debug will not be saved in database", account).arg(Translator::tr(save, account)));
			}
		}
		else
		{
			return new ApiManager::ApiString(GetSettings("Save/" + save, 0).toString());
		}
	}
	else if(action == "display")
	{
		if(!hRequest.HasArg("display"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("display", GetName()));

		QString display = hRequest.GetArg("display");

		if(hRequest.HasArg("value"))
		{
			QString value = hRequest.GetArg("value");
			SetSettings("Display/" + display, value);
			if(value == "1")
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' debug will be displayed in logs", account).arg(Translator::tr(display, account)));
			}
			else
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' debug will not be displayed in logs", account).arg(Translator::tr(display, account)));
			}
		}
		else
		{
			return new ApiManager::ApiString(GetSettings("Display/" + display, 0).toString());
		}
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
