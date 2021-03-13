#include <QDateTime>
#include <QStringList>
#include <QtSql/QtSql>
#include "dbmanager.h"
#include "plugin_status.h"
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

PluginStatus::PluginStatus():PluginInterface("status", "Get bunny status", SystemPlugin)
{
}

void PluginStatus::OnBunnyConnect(Bunny * b)
{
	SendRequestStatus(b, "getshortconfig");
}

PluginStatus::~PluginStatus()
{
}

bool PluginStatus::XmppBunnyMessage(Bunny * b, QByteArray const& data)
{
	QRegExp rx("<iq[^>]*><command[^>]*node='get([^']*)' status='completed'[^>]*>(.*)</iq>");
	rx.setMinimal(true);
	if (rx.indexIn(data) != -1)
	{
		QString message = rx.cap(2);
		QString type = rx.cap(1);
		type = type.replace(QRegExp("state$"), "");

		QMap<QString, QVariant> status;
		rx.setPattern("<item>(.+)</item>");
		if (rx.indexIn(message) != -1)
		{
			QStringList decodes = GetSettings("Decode", QStringList()).toStringList();
			QString result = rx.cap(1);
			rx.setPattern("<field var='([^']+)'><value>([^<]+)</value></field>");
			int pos = 0;
			while ((pos = rx.indexIn(result, pos)) != -1)
			{
				if(decodes.contains(rx.cap(1)))
				{
					status.insert(rx.cap(1), QString(QByteArray::fromBase64(rx.cap(2).toLatin1())));
				}
				else
				{
					status.insert(rx.cap(1), rx.cap(2));
				}

				pos += rx.matchedLength();
			}

			if(status.count())
			{
				if(GetSettings("Save/" + type, 0).toInt() != 0)
				{
					QSqlDatabase db = DbManager::getDb();
					bool close = DbManager::openDbIfNeeded();
					QSqlQuery *query = new QSqlQuery(db);

					if(GetSettings("Unique/" + type, 0).toInt() != 0)
					{
						query->prepare("DELETE FROM status_" + type + " WHERE `mac`=:mac");
						query->bindValue(":mac", QString(b->GetID()));
						query->exec();
					}

					QStringList values;
					QMapIterator<QString, QVariant> i(status);
					while (i.hasNext())
					{
						i.next();
						values.append("`" + i.key() + "`=:" + i.key());
					}
					query->prepare("INSERT INTO status_" + type + " SET `date`=:date, `mac`=:mac, " + values.join(", "));
					query->bindValue(":date", QDateTime::currentDateTime().toString("yyyy-MM-dd hh:mm:ss"));
					query->bindValue(":mac", QString(b->GetID()));

					QMapIterator<QString, QVariant> i2(status);
					while (i2.hasNext())
					{
						i2.next();
						query->bindValue(":" + i2.key(), i2.value());
					}
					if(!query->exec())
					{
						LogError("Can't save status values");
					}
					delete query;
					if(close)
					{
						DbManager::releaseDb();
					}
				}
				if(GetSettings("Display/" + type, 0).toInt() != 0)
				{
					QMapIterator<QString, QVariant> i(status);
					while (i.hasNext())
					{
						i.next();
						LogInfo(QString("|-- %1=%2").arg(i.key(), i.value().toString()));
					}
				}
				return true;
			}
		}

	}
	return false;
}

void PluginStatus::SendRequestStatus(Bunny * b, QString t)
{
	QString data = QString("<iq type='set' to='%1@%2/%3' from='%2@%2/server' id='exec1'><command xmlns='http://jabber.org/protocol/commands' node='%4' action='execute'/></iq>").arg(QString(b->GetID()), GlobalSettings::GetString("OpenJabNabServers/XmppServer"), QString(b->GetXmppResource()), t);
	b->SendExpertData(data.toLatin1());
}

void PluginStatus::SendRequestStatus(Bunny * b)
{
	SendRequestStatus(b, "getsilentstate");
}

void PluginStatus::InitApiCalls()
{
        DECLARE_PLUGIN_BUNNY_API_CALL("status()", &PluginStatus::Api_Status);
        DECLARE_PLUGIN_API_CALL("config()", &PluginStatus::Api_Config);
}

PLUGIN_BUNNY_API_CALL(PluginStatus::Api_Status)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "silent")
	{
		SendRequestStatus(bunny);
        	return new ApiManager::ApiOk(Translator::tr("'%1' status requested, waiting answer", account).arg(Translator::tr("silent", account)));
	}
	else if(action == "running")
	{
		SendRequestStatus(bunny, "getrunningstate");
        	return new ApiManager::ApiOk(Translator::tr("'%1' status requested, waiting answer", account).arg(Translator::tr("running", account)));
	}
	else if(action == "shortconfig")
	{
		SendRequestStatus(bunny, "getshortconfig");
        	return new ApiManager::ApiOk(Translator::tr("'%1' status requested, waiting answer", account).arg(Translator::tr("shortconfig", account)));
	}
	else if(action == "config")
	{
		SendRequestStatus(bunny, "getconfig");
        	return new ApiManager::ApiOk(Translator::tr("'%1' status requested, waiting answer", account).arg(Translator::tr("config", account)));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginStatus::Api_Config)
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
        			return new ApiManager::ApiOk(Translator::tr("'%1' status will be saved in database", account).arg(Translator::tr(save, account)));
			}
			else
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' status will not be saved in database", account).arg(Translator::tr(save, account)));
			}
		}
		else
		{
			return new ApiManager::ApiString(GetSettings("Save/" + save, 0).toString());
		}
	}
	else if(action == "unique")
	{
		if(!hRequest.HasArg("unique"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("unique", GetName()));

		QString unique = hRequest.GetArg("unique");

		if(hRequest.HasArg("value"))
		{
			QString value = hRequest.GetArg("value");
			SetSettings("Unique/" + unique, value);
			if(value == "1")
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' status will be unique in database", account).arg(Translator::tr(unique, account)));
			}
			else
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' status will not be unique in database", account).arg(Translator::tr(unique, account)));
			}
		}
		else
		{
			return new ApiManager::ApiString(GetSettings("Unique/" + unique, 0).toString());
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
        			return new ApiManager::ApiOk(Translator::tr("'%1' status will be displayed in logs", account).arg(Translator::tr(display, account)));
			}
			else
			{
        			return new ApiManager::ApiOk(Translator::tr("'%1' status will not be displayed in logs", account).arg(Translator::tr(display, account)));
			}
		}
		else
		{
			return new ApiManager::ApiString(GetSettings("Display/" + display, 0).toString());
		}
	}
	else if(action == "decode")
	{
		if(!hRequest.HasArg("subaction"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("subaction", GetName()));

		QString subaction = hRequest.GetArg("subaction");

		if(subaction == "add")
		{
			if(!hRequest.HasArg("decode"))
				return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("decode", GetName()));

			QString decode = hRequest.GetArg("decode");

			QStringList list = GetSettings("Decode", QStringList()).toStringList();
			if(!list.contains(decode))
			{
				list.append(decode);
				list.removeDuplicates();
				SetSettings("Decode", list);
				return new ApiManager::ApiOk(Translator::tr("Added '%1' in the list of decoded values", account).arg(decode));
			}
			return new ApiManager::ApiError(Translator::tr("'%1' is already in the list of decoded values", account).arg(decode));
		}
		else if(subaction == "del")
		{
			if(!hRequest.HasArg("decode"))
				return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("decode", GetName()));

			QString decode = hRequest.GetArg("decode");

			QStringList list = GetSettings("Decode", QStringList()).toStringList();
			if(list.contains(decode))
			{
				list.removeAll(decode);
				SetSettings("Decode", list);
				return new ApiManager::ApiOk(Translator::tr("Removed '%1' from the list of decoded values", account).arg(decode));
			}
			return new ApiManager::ApiError(Translator::tr("'%1' is not in the list of decoded values", account).arg(decode));
		}
		else if(subaction == "list")
		{
			return new ApiManager::ApiList(GetSettings("Decode", QStringList()).toStringList());
		}
		else
		{
			return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("subaction", GetName()));
		}
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
