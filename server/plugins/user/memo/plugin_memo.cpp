#include <QDateTime>
#include <QCryptographicHash>
#include <QMapIterator>
#include <QRegExp>
#include <memory>
#include "bunny.h"
#include "bunnymanager.h"
#include "httprequest.h"
#include "log.h"
#include "cron.h"
#include "messagepacket.h"
#include "plugin_memo.h"
#include "settings.h"
#include "ttsmanager.h"
#include "translator.h"

PluginMemo::PluginMemo():PluginInterface("memo", "Memo", BunnyV1Plugin | BunnyV2Plugin | CronPlugin)
{
	std::unique_ptr<QDir> dir(GetLocalHTTPFolder());
	if(dir.get())
	{
		memoFolder = *dir;
	}
}

PluginMemo::~PluginMemo()
{
	Cron::UnregisterAll(this);
}

void PluginMemo::OnCron(Bunny * b, QVariant v, unsigned int)
{
	TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
	TTSAnswer msg = TTSManager::CreateSound(v.value<QString>(), b->GetVoice(), b->GetLanguage(), format, false);
	TTSLog(b->GetID(), GetName(), msg);
	if(b->GetVersion() == 2)
	{
		QByteArray message = "MU " + msg.file.toLatin1() + "\nMW\n";
		b->SendPacket(MessagePacket(message), GetName());
	}
	else if(b->GetVersion() == 1)
	{
		AddSoundToSend(b, msg.file);
/*
		QStringList list = soundToSend.value(b, QStringList());
		list.append(msg.file);
		soundToSend.insert(b, list);
*/
	}
}

void PluginMemo::OnBunnyConnect(Bunny * b)
{
	QMap<QString, QVariant> schedules = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();

	QMap<QString, QVariant> listDaily = b->GetPluginSetting(GetName(), "DailyWebcasts", QMap<QString, QVariant>()).toMap();
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap();
	if(listDaily.count() || list.count())
	{
		QMapIterator<QString, QVariant> i(listDaily);
		while (i.hasNext())
		{
			i.next();
			schedules.insert("0|" + i.key(), i.value());

		}
		b->RemovePluginSetting(GetName(), "DailyWebcasts");
		QMapIterator<QString, QVariant> i2(list);
		while (i2.hasNext())
		{
			i2.next();
			schedules.insert(i2.key(), i2.value());
		}
		b->RemovePluginSetting(GetName(), "Webcasts");
		b->SetPluginSetting(GetName(), "Schedules", schedules);
	}

	list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> i3(list);
	while (i3.hasNext())
	{
		i3.next();
		QStringList when = i3.key().split("|");
		int day = when.at(0).toInt();
		QString time = when.at(1);
		QString message = i3.value().toString();
		Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(message));
	}
}

void PluginMemo::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginMemo::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginMemo::Api_Schedule);
}

PLUGIN_BUNNY_API_CALL(PluginMemo::Api_Schedule)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "edit")
	{
		if(!hRequest.HasArg("time"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		int day = 0;
		if(hRequest.HasArg("day"))
			day = hRequest.GetArg("day").toInt();

		if(!hRequest.HasArg("message"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("message", GetName()));

		QString message = hRequest.GetArg("message");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(list.contains(QString::number(day) + "|" + time))
		{
			if(day == 0)
			{
				Cron::RegisterDaily(this, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(message));
			}
			else
			{
				Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(message));
			}

			list.insert(QString::number(day) + "|" + time, message);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			if(day == 0)
				return new ApiAnswers::Ok(Translator::tr("Schedule at '%1' edited for bunny '%2'", account).arg(time, QString(bunny->GetID())));
			return new ApiAnswers::Ok(Translator::tr("Schedule on '%1' at '%2' edited for bunny '%3'", account).arg(QString::number(day), time, QString(bunny->GetID())));
		}
		if(day == 0)
			return new ApiAnswers::Error(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		return new ApiAnswers::Error(Translator::tr("No schedule on '%1' at '%2' for bunny '%3'", account).arg(QString::number(day), time, QString(bunny->GetID())));
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("time"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		int day = 0;
		if(hRequest.HasArg("day"))
			day = hRequest.GetArg("day").toInt();

		if(!hRequest.HasArg("message"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("message", GetName()));

		QString message = hRequest.GetArg("message");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(!list.contains(QString::number(day) + "|" + time))
		{
			if(day == 0)
			{
				Cron::RegisterDaily(this, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(message));
			}
			else
			{
				Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(message));
			}

			list.insert(QString::number(day) + "|" + time, message);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			return new ApiAnswers::Ok(Translator::tr("Add schedule at '%1' to bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		if(day == 0)
			return new ApiAnswers::Error(Translator::tr("Schedule at '%1' already exists for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		return new ApiAnswers::Error(Translator::tr("Schedule on '%1', at '%2' already exists for bunny '%3'", account).arg(QString::number(day), time, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("time"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		int day = 0;
		if(hRequest.HasArg("day"))
			day = hRequest.GetArg("day").toInt();

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();

		QString when = QString::number(day) + "|" + time;
		if(list.contains(when))
		{
			list.remove(when);
			bunny->SetPluginSetting(GetName(), "Schedules", list);

        		OnBunnyDisconnect(bunny);
        		OnBunnyConnect(bunny);

			return new ApiAnswers::Ok(Translator::tr("Schedule at '%1' removed for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

