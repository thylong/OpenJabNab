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
#include "plugin_dicton.h"
#include "settings.h"
#include "ttsmanager.h"
#include "translator.h"

PluginDicton::PluginDicton():PluginInterface("dicton", "Dicton",BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | CronPlugin | MessagePlugin | ApiPlugin | RfidPlugin)
{
	InitData();
}

PluginDicton::~PluginDicton()
{
	Cron::UnregisterAll(this);
}

QString PluginDicton::OnApiGet(Bunny *b, QVariant )
{
	sayDicton(b);
	return QString();
}

void PluginDicton::OnCron(Bunny * b, QVariant, unsigned int)
{
	sayDicton(b, true);
}

bool PluginDicton::OnClick(Bunny * b, PluginInterface::ClickType)
{
	sayDicton(b);
	return true;
}

bool PluginDicton::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		sayDicton(b);
		return true;
	}
	return false;
}

bool PluginDicton::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("dicton", b), command))
	{
		sayDicton(b);
		return true;
	}
	return false;
}

void PluginDicton::sayDicton(Bunny * b)
{
	sayDicton(b, false);
}

void PluginDicton::sayDicton(Bunny * b, bool save)
{
	QStringList files;
	if(b->IsConnected())
	{
		QString today = QString::fromUtf8("Le dicton du jour : ");
		TTSAnswer file = TTSManager::CreateSound(today, b->GetVoice(), b->GetLanguage());
		TTSLog(b->GetID(), GetName(), file);
		files.append(file.file);

		QDateTime currentDay = Translator::GetCurrentTime(b->GetGlobalSetting("TimeZone","UTC").toString());
		int month = currentDay.toString("M").toInt();
		int day = currentDay.toString("d").toInt();

		QString dicton = data.value(month).value(day);
		file = TTSManager::CreateSound(dicton, b->GetVoice(), b->GetLanguage());
		TTSLog(b->GetID(), GetName(), file);
		files.append(file.file);

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

void PluginDicton::OnBunnyConnect(Bunny * b)
{
	QStringList list = b->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList();
	foreach(QString time, list) {
		Cron::RegisterDaily(this, Cron::mkTime(time), b, Cron::Classic, QVariant());
	}
}

void PluginDicton::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginDicton::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginDicton::Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("language()", &PluginDicton::Api_Language);
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginDicton::Api_RFID);
}

PLUGIN_BUNNY_API_CALL(PluginDicton::Api_RFID)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::List(bunny->GetPluginSetting(GetName(), "RFID", QStringList()).toStringList());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		QStringList list = bunny->GetPluginSetting(GetName(), "RFID", QStringList()).toStringList();
		if(!list.contains(tag))
		{
			list.append(tag);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Associate(bunny, this);
			return new ApiAnswers::Ok(Translator::tr("Add RFID '%1' for bunny '%2'").arg(tag, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("RFID '%1' already assigned to bunny '%2'", account).arg(tag, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		QStringList list = bunny->GetPluginSetting(GetName(), "RFID", QStringList()).toStringList();
		if(list.contains(tag))
		{
			list.removeAll(tag);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Dissociate(bunny);

			return new ApiAnswers::Ok(Translator::tr("RFID '%1' removed for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("RFID '%1' is not assign to bunny '%2'", account).arg(tag, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginDicton::Api_Schedule)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::List(bunny->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("time"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString hTime = hRequest.GetArg("time");

		QStringList list = bunny->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList();
		if(!list.contains(hTime))
		{
			Cron::RegisterDaily(this, Cron::mkTime(hTime), bunny, Cron::Classic, QVariant());
			list.append(hTime);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			return new ApiAnswers::Ok(Translator::tr("Add schedule at '%1' for bunny '%2'", account).arg(hTime, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Schedule already exists at '%1' for bunny '%2'", account).arg(hTime, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("time"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QStringList list = bunny->GetPluginSetting(GetName(), "Schedules", QStringList()).toStringList();
		QString time = hRequest.GetArg("time");
		if(list.contains(time))
		{
			list.removeAll(time);
			bunny->SetPluginSetting(GetName(), "Schedules", list);

			// Recreate crons
			OnBunnyDisconnect(bunny);
			OnBunnyConnect(bunny);
			return new ApiAnswers::Ok(Translator::tr("Remove schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginDicton::Api_Language)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::List(GetLanguages());
	}
	else if(action == "get")
	{
		return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "Language", bunny->GetLanguage()).toString());
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("lng"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

		QString lng = hRequest.GetArg("lng");

		if(!GetLanguages().contains(lng))
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("lng", GetName()));

		bunny->SetPluginSetting(GetName(), "Language", lng);
		return new ApiAnswers::Ok(Translator::tr("Bunny language is now '%1' for plugin '%2'").arg(lng, GetName()));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
