#include <QDateTime>
#include <QCryptographicHash>
#include <QXmlStreamReader>
#include <QMapIterator>
#include <QRegExp>
#include <QUrl>
#include <memory>
#include "bunny.h"
#include "bunnymanager.h"
#include "httprequest.h"
#include "log.h"
#include "cron.h"
#include "messagepacket.h"
#include "plugin_dayof.h"
#include "settings.h"
#include "ttsmanager.h"
#include "translator.h"

PluginDayof::PluginDayof()
  : PluginInterface("dayof", "National and international days",BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | CronPlugin | MessagePlugin | ApiPlugin | RfidPlugin)
{
	InitData();
}

PluginDayof::~PluginDayof()
{
	Cron::UnregisterAll(this);
}

QString PluginDayof::OnApiGet(Bunny *b, QVariant )
{
	sayDayof(b);
	return QString();
}

void PluginDayof::OnCron(Bunny * b, QVariant, unsigned int)
{
	sayDayof(b, true);
}

bool PluginDayof::OnClick(Bunny * b, PluginInterface::ClickType)
{
	sayDayof(b);
	return true;
}

bool PluginDayof::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		sayDayof(b);
		return true;
	}
	return false;
}

bool PluginDayof::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("dayof", b), command))
	{
		sayDayof(b);
		return true;
	}
	return false;
}

void PluginDayof::sayDayof(Bunny * b)
{
	sayDayof(b, false);
}

void PluginDayof::sayDayof(Bunny * b, bool save)
{
	QStringList files;
	if(b->IsConnected())
	{
		
		QDateTime currentDay = Translator::GetCurrentTime(b->GetGlobalSetting("TimeZone","UTC").toString());
		int month = currentDay.toString("M").toInt();
		int day = currentDay.toString("d").toInt();

		QStringList dayofs = data.value(month).values(day);
		if(dayofs.size() > 0)
		{
			QString today = QString::fromUtf8("Aujourd'hui, c'est ");
			TTSAnswer file = TTSManager::CreateSound(today, b->GetVoice(), b->GetLanguage());
			TTSLog(b->GetID(), GetName(), file);
			files.append(file.file);

			for (int i = 0; i < dayofs.size(); ++i)
			{
				QString dayof = dayofs.at(i);
				file = TTSManager::CreateSound(dayof, b->GetVoice(), b->GetLanguage());
				TTSLog(b->GetID(), GetName(), file);
				files.append(file.file);
				if(i+1 < dayofs.size())
				{
					QString separator = ", et ";
					file = TTSManager::CreateSound(separator, b->GetVoice(), b->GetLanguage());
					TTSLog(b->GetID(), GetName(), file);
					files.append(file.file);
				}
			}
		}
		else
		{
			QString today = QString::fromUtf8("Aujourd'hui, c'est un jour ordinaire");
			TTSAnswer file = TTSManager::CreateSound(today, b->GetVoice(), b->GetLanguage());
			TTSLog(b->GetID(), GetName(), file);
			files.append(file.file);
		}

		QByteArray message;
		foreach(QString file, files)
		{
			message += "MU " + file + "\nMW\n";
		}
		b->SendPacket(MessagePacket(message), GetName());
		if(save)
		{
			SaveMessage(b, files);
		}
	}
}

void PluginDayof::OnBunnyConnect(Bunny * b)
{
	QStringList list = b->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList();
	foreach(QString time, list) {
		Cron::RegisterDaily(this, Cron::mkTime(time), b, Cron::Classic, QVariant());
	}
}

void PluginDayof::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginDayof::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginDayof::Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("language()", &PluginDayof::Api_Language);
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginDayof::Api_RFID);
}

PLUGIN_BUNNY_API_CALL(PluginDayof::Api_RFID)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiList(bunny->GetPluginSetting(GetName(), "RFID", QStringList()).toStringList());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		QStringList list = bunny->GetPluginSetting(GetName(), "RFID", QStringList()).toStringList();
		if(!list.contains(tag))
		{
			list.append(tag);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Associate(bunny, this);
			return new ApiManager::ApiOk(Translator::tr("Add RFID '%1' for bunny '%2'").arg(tag, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("RFID '%1' already assigned to bunny '%2'", account).arg(tag, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		QStringList list = bunny->GetPluginSetting(GetName(), "RFID", QStringList()).toStringList();
		if(list.contains(tag))
		{
			list.removeAll(tag);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Dissociate(bunny);

			return new ApiManager::ApiOk(Translator::tr("RFID '%1' removed for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("RFID '%1' is not assign to bunny '%2'", account).arg(tag, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginDayof::Api_Schedule)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiList(bunny->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString hTime = hRequest.GetArg("time");

		QStringList list = bunny->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList();
		if(!list.contains(hTime))
		{
			Cron::RegisterDaily(this, Cron::mkTime(hTime), bunny, Cron::Classic, QVariant());
			list.append(hTime);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			return new ApiManager::ApiOk(Translator::tr("Add schedule at '%1' for bunny '%2'", account).arg(hTime, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Schedule already exists at '%1' for bunny '%2'", account).arg(hTime, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QStringList list = bunny->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList();
		QString time = hRequest.GetArg("time");
		if(list.contains(time))
		{
			list.removeAll(time);
			bunny->SetPluginSetting(GetName(), "Schedules", list);

			// Recreate crons
			OnBunnyDisconnect(bunny);
			OnBunnyConnect(bunny);
			return new ApiManager::ApiOk(Translator::tr("Remove schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginDayof::Api_Language)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiList(GetLanguages());
	}
	else if(action == "get")
	{
		return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "Language", bunny->GetLanguage()).toString());
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("lng"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

		QString lng = hRequest.GetArg("lng");

		if(!GetLanguages().contains(lng))
			return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("lng", GetName()));

		bunny->SetPluginSetting(GetName(), "Language", lng);
		return new ApiManager::ApiOk(Translator::tr("Bunny language is now '%1' for plugin '%2'").arg(lng, GetName()));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
