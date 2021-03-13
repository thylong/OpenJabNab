#include <QDateTime>
#include <QStringList>
#include "plugin_volume.h"
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
#include "ttsmanager.h"

// Sound from 0 to 255
// 0 = sound volume managed by rear button
// 1 = very loud
// 255 = no sound

PluginVolume::PluginVolume():PluginInterface("volume", "Change sound volume", BunnyV2Plugin | CronPlugin | ApiPlugin | DevPlugin)
{
}

PluginVolume::~PluginVolume()
{
	Cron::UnregisterAll(this);
}

QString PluginVolume::OnApiSet(Bunny *b, QVariant v)
{
	int vol = v.value<int>();
	if(b->IsConnected())
	{
       		b->SetPluginSetting(GetName(), "currentsound", vol);
		b->SendPacket(AmbientPacket(AmbientPacket::Service_SoundVol, vol), GetName());
	}
	return QString();
}

QString PluginVolume::OnApiGet(Bunny *b, QVariant)
{
	return QString("<sound>" + b->GetPluginSetting(GetName(), "currentsound", 0).toString() + "</sound>");
}

void PluginVolume::OnCron(Bunny * b, QVariant v, unsigned int)
{
	int vol = v.value<int>();
	if(b->IsConnected())
	{
       		b->SetPluginSetting(GetName(), "currentsound", vol);
		b->SendPacket(AmbientPacket(AmbientPacket::Service_SoundVol, vol), GetName());
	}
}

void PluginVolume::OnBunnyConnect(Bunny * b)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "SoundChange", QMap<QString, QVariant>()).toMap();
	if(list.count())
	{
		b->SetPluginSetting(GetName(), "Schedules", list);
		b->RemovePluginSetting(GetName(), "SoundChange");
	}
	RegisterCrons(b);
}

void PluginVolume::OnBunnyDisconnect(Bunny * b)
{
	CleanCrons(b);
}

void PluginVolume::OnInitPacket(const Bunny * bunny, AmbientPacket & a, SleepPacket &)
{
	int sound = bunny->GetPluginSetting(GetName(), "sound", 0).toInt();
	a.SetServiceValue(AmbientPacket::Service_SoundVol, sound);
}

bool PluginVolume::XmppBunnyMessage(Bunny * b, QByteArray const& data)
{
	QRegExp rx("<message[^>]*>(.*)</message>");
	if (rx.indexIn(data) != -1)
	{
		QString message = rx.cap(1);
		rx.setPattern("<sound xmlns=\"OJN:nabaztag:sound\">(\\d+)</sound>");
		if (rx.indexIn(message) != -1)
		{
			int vol = rx.cap(1).toInt();
       			b->SetPluginSetting(GetName(), "currentsound", vol);
			return true;
		}
	}
	return false;
}
void PluginVolume::CleanCrons(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginVolume::RegisterCrons(Bunny * b)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext())
	{
		i.next();
		QStringList when = i.key().split("|");
		if(when.count() == 2)
		{
			int day = when.at(0).toInt();
			QString time = when.at(1);
			int vol = i.value().toInt();

			if(day == 0)
				Cron::RegisterDaily(this, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(vol));
			else
				Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(vol));
		}
		else
		{
			list.remove(i.key());
        		b->SetPluginSetting(GetName(), "Schedules", list);
		}
	}
}

void PluginVolume::InitApiCalls()
{
/*
        DECLARE_PLUGIN_BUNNY_API_CALL("setSound(vol)", &PluginVolume::Api_SetSound);
        DECLARE_PLUGIN_BUNNY_API_CALL("getSound()", &PluginVolume::Api_GetSound);
        DECLARE_PLUGIN_BUNNY_API_CALL("getCurrent()", &PluginVolume::Api_GetCurrent);
        DECLARE_PLUGIN_BUNNY_API_CALL("pollCurrent()", &PluginVolume::Api_PollCurrent);
        DECLARE_PLUGIN_BUNNY_API_CALL("addChange(vol,day,time)", &PluginVolume::Api_AddChange);
        DECLARE_PLUGIN_BUNNY_API_CALL("removeChange(day,time)", &PluginVolume::Api_RemoveChange);
        DECLARE_PLUGIN_BUNNY_API_CALL("getChanges()", &PluginVolume::Api_GetChanges);
*/
        DECLARE_PLUGIN_BUNNY_API_CALL("sound()", &PluginVolume::Api_Sound);
        DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginVolume::Api_Schedule);
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_Schedule)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		if(!hRequest.HasArg("vol"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("vol", GetName()));

		QString sound = hRequest.GetArg("vol");

		if(!hRequest.HasArg("day"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("day", GetName()));

		int day = hRequest.GetArg("day").toInt();

		QString key = QString::number(day) + "|" + time;

		if(!bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap().contains(key))
		{
			if(day >= 1 && day <= 7)
				Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(sound));
			else
				Cron::RegisterDaily(this, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(sound));

			QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
			list.insert(key, sound);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			CleanCrons(bunny);
			RegisterCrons(bunny);
			return new ApiManager::ApiOk(Translator::tr("Add schedule at '%1' to bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}	
		return new ApiManager::ApiError(Translator::tr("Schedule at '%1' already exists for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		if(!hRequest.HasArg("day"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("day", GetName()));

		int day = hRequest.GetArg("day").toInt();

		QString key = QString::number(day) + "|" + time;

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(list.contains(key))
		{
			list.remove(key);
			bunny->SetPluginSetting(GetName(), "Schedules", list);

        		OnBunnyDisconnect(bunny);
        		OnBunnyConnect(bunny);

			return new ApiManager::ApiOk(Translator::tr("Schedule at '%1' removed for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_Sound)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "poll")
	{
		bunny->SendPacket(MessagePacket("GV\n"), GetName());
        	return new ApiManager::ApiOk(Translator::tr("Volume asked to bunny, waiting answer", account));
	}
	else if(action == "test")
	{
		TTSManager::OutputFormat format = bunny->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;

		TTSAnswer sound = TTSManager::CreateSound(Translator::tr("Test sound volume at %1 for bunny '%2'", account).arg(bunny->GetPluginSetting(GetName(), "currentsound", 0).toString(), bunny->GetBunnyName()), bunny->GetVoice(), bunny->GetLanguage(), format, false);
		TTSLog(bunny->GetID(), GetName(), sound);

		if(bunny->GetVersion() == 1)
		{
			AddSoundToSend(bunny, sound.file);
		}
		else
		{
			if(hRequest.HasArg("volume"))
			{
				QString volume = hRequest.GetArg("volume");
				QString previous = bunny->GetPluginSetting(GetName(), "currentsound", 0).toString();
				bunny->SendPacket(MessagePacket("FV " + volume.toLatin1() + "\nMW\nMU " + sound.file.toLatin1() + "\nMW\nFV " + previous.toLatin1() + "\nMW\n"), GetName());
				return new ApiManager::ApiOk(Translator::tr("Sending test volume at '%1' to bunny '%2'", account).arg(volume, QString(bunny->GetID())));
			}
			else
			{
				bunny->SendPacket(MessagePacket("MU " + sound.file.toLatin1() + "\nMW\n"), GetName());
			}
		}
		return new ApiManager::ApiOk(Translator::tr("Sending test volume at '%1' to bunny '%2'", account).arg(bunny->GetPluginSetting(GetName(), "currentsound", 0).toString(), QString(bunny->GetID())));
	}
	else if(action == "read")
	{
        	return new ApiManager::ApiOk(bunny->GetPluginSetting(GetName(), "currentsound", 0).toString());
	}
	else if(action == "get")
	{
        	return new ApiManager::ApiOk(bunny->GetPluginSetting(GetName(), "sound", 0).toString());
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("vol"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("vol", GetName()));

		QString sound = hRequest.GetArg("vol");
		bunny->SetPluginSetting(GetName(), "sound", sound);
		bunny->SendPacket(AmbientPacket(AmbientPacket::Service_SoundVol, sound.toInt()), GetName());

		return new ApiManager::ApiOk(Translator::tr("Sound volume set to '%1'", account).arg(sound));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

/*
PLUGIN_BUNNY_API_CALL(PluginVolume::Api_GetCurrent)
{
        Q_UNUSED(account);
	Q_UNUSED(hRequest);

        return new ApiManager::ApiOk(bunny->GetPluginSetting(GetName(), "currentsound", 0).toString());
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_PollCurrent)
{
        Q_UNUSED(account);
	Q_UNUSED(hRequest);

	bunny->SendPacket(MessagePacket("GV\n"), GetName());
        return new ApiManager::ApiOk("Volume asked to bunny, waiting answer");
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_GetSound)
{
        Q_UNUSED(account);
	Q_UNUSED(hRequest);

        return new ApiManager::ApiOk(bunny->GetPluginSetting(GetName(), "sound", 0).toString());
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_SetSound)
{
        Q_UNUSED(account);

        QString sound = hRequest.GetArg("vol");
        // Save new config
        bunny->SetPluginSetting(GetName(), "sound", sound);

	// Send sound to bunny
	bunny->SendPacket(AmbientPacket(AmbientPacket::Service_SoundVol, sound.toInt()), GetName());

	return new ApiManager::ApiOk(QString("Sound volume set to '%1'").arg(sound));
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_AddChange)
{
	Q_UNUSED(account);

        QString sound = hRequest.GetArg("vol");
	QString hTime = hRequest.GetArg("time");
	QTime w = Cron::mkTime(hTime);
	if(!w.isValid())
		return new ApiManager::ApiError(QString("Bad time '%1'").arg(hRequest.GetArg("time")));
	int day = hRequest.GetArg("day").toInt();
	QString key = QString::number(day) + "|" + hTime;

	if(!bunny->GetPluginSetting(GetName(), "SoundChange", QMap<QString, QVariant>()).toMap().contains(key))
    	{
		if(day >= 1 && day <= 7)
        		Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, w, bunny, Cron::Classic, QVariant::fromValue(sound));
		else
        		Cron::RegisterDaily(this, w, bunny, Cron::Classic, QVariant::fromValue(sound));
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "SoundChange", QMap<QString, QVariant>()).toMap();
        	list.insert(key, sound);
        	bunny->SetPluginSetting(GetName(), "SoundChange", list);
		CleanCrons(bunny);
		RegisterCrons(bunny);
		//UpdateState(bunny);
        	return new ApiManager::ApiOk(QString("Add sound change at '%1' to bunny '%2'").arg(hRequest.GetArg("time"), QString(bunny->GetID())));
    	}	
    	return new ApiManager::ApiError(QString("Webcast at '%1' already exists for bunny '%2'").arg(hRequest.GetArg("time"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_RemoveChange)
{
	Q_UNUSED(account);

	int hDay = hRequest.GetArg("day").toInt();
	if(!hRequest.HasArg("time"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "SoundChange", QMap<QString, QVariant>()).toMap();
	QString time = hRequest.GetArg("time");
	QString key = QString::number(hDay) + "|" + time;

	if(list.contains(key))
	{
		list.remove(key);
		bunny->SetPluginSetting(GetName(), "SoundChange", list);

		CleanCrons(bunny);
		RegisterCrons(bunny);
		return new ApiManager::ApiOk(Translator::tr("Remove sound change at '%1' for bunny '%2'", account).arg(hRequest.GetArg("time"), QString(bunny->GetID())));
	}
	return new ApiManager::ApiError(Translator::tr("No sound change at '%1' for bunny '%2'", account).arg(hRequest.GetArg("time"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginVolume::Api_GetChanges)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "SoundChange", QMap<QString, QVariant>()).toMap());
}
*/
