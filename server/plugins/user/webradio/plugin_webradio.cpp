#include <QMap>
#include <QMapIterator>
#include <QTime>
#include "plugin_webradio.h"
#include "account.h"
#include "bunny.h"
#include "cron.h"
#include "bunnymanager.h"
#include "messagepacket.h"
#include "translator.h"

PluginWebradio::PluginWebradio():PluginInterface("webradio", "WebRadio", BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | ZtampPlugin | CronPlugin | RfidPlugin)
{
}

PluginWebradio::~PluginWebradio()
{
    Cron::UnregisterAll(this);
}

bool PluginWebradio::Init()
{
	presets.clear();
	presets = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
	return true;
}

void PluginWebradio::OnCron(Bunny * b, QVariant v, unsigned int)
{
	QString name = v.value<QString>();
        streamPresetWebradio(b, name);
}

QString PluginWebradio::getRadioName(QString name)
{
	return ((!name.toLower().contains("radio") ? "radio " : "") + name).toLower();
}

bool PluginWebradio::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (command.contains("radio"))
	{
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant>::iterator i;
		int max = 0;
		QString key = "";
		for (i = list.begin(); i != list.end(); ++i)
		{
			int p = getPertinence(getRadioName(i.key()), command);
			if( p > max )
			{
				max = p;
				key = i.key();
			}
		}
		QMap<QString, QVariant>::iterator j;
		for (j = presets.begin(); j != presets.end(); ++j)
		{
			int p = getPertinence(getRadioName(j.key()), command);
			if( p > max )
			{
				max = p;
				key = j.key();
			}
		}
		if(max > 0)
		{
			return streamPresetWebradio(b, key);
		}
		else
		{
			QString radio = b->GetPluginSetting(GetName(), "DefaultWebradio", QString()).toString();
			return streamPresetWebradio(b, radio);
		}
	}
	return false;
}

bool PluginWebradio::OnRFID(Ztamp * z, Bunny * b)
{
	QString radio = z->GetPluginSetting(GetName(), QString("Play"), QString()).toString();
	if(radio != "")
	{
		return streamPresetWebradio(b, radio);
	}
	return false;
}

bool PluginWebradio::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
        	return streamPresetWebradio(b, list.value(rfid).toString());
	}
	return false;
}

bool PluginWebradio::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	QString radio = "";
	if(type == PluginInterface::SingleClick)
	{
		radio = b->GetPluginSetting(GetName(), "DefaultWebradio", QString()).toString();
	}
	else if (type == PluginInterface::DoubleClick)
	{
		radio = b->GetPluginSetting(GetName(), "OtherWebradio", b->GetPluginSetting(GetName(), "DefaultWebradio", QString()).toString()).toString();
	}
	if(radio.length())
	{
		if(streamPresetWebradio(b, radio))
			return true;
		return false;
	}
	return false;
}

void PluginWebradio::OnBunnyConnect(Bunny * b)
{
	QMap<QString, QVariant> rfid = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QStringList keys = b->GetPluginSettings(GetName());
	foreach(QString key, keys)
	{
		if(key.contains("RFIDPlay/"))
		{
			LogInfo(QString("Bunny %1 has old %2 setup (key is %3), converting and removing").arg(QString(b->GetID()), GetName(), key));
			QStringList k = key.split("/");
			if(k.at(1).length() == 12)
			{
				rfid.insert(k.at(1), b->GetPluginSetting(GetName(), key, QString()).toString());
			}
			b->RemovePluginSetting(GetName(), key);
		}
	}
	b->SetPluginSetting(GetName(), "RFID", rfid);

	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap();
	if(list.count())
	{
		LogInfo(QString("Bunny %1 has old %2 setup (key is %3), converting and removing").arg(QString(b->GetID()), GetName(), "Webcasts"));
		b->SetPluginSetting(GetName(), "Schedules", list);
		b->RemovePluginSetting(GetName(), "Webcasts");
	}

	list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	if(list.count())
	{
		bool change = false;
		QMapIterator<QString, QVariant> i(list);
		while (i.hasNext())
		{
			i.next();
			QString time = i.key();
			QString name = i.value().toString();
			if(!time.contains("|"))
			{
				change = true;
				list.remove(time);
				time = "0|" + time;
				list.insert(time, name);
			}
		}
		if(change)
		{
			b->SetPluginSetting(GetName(), "Schedules", list);
		}
	}

	list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();

	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext())
	{
		i.next();
		QStringList when = i.key().split("|");
		if(when.count() != 2)
		{
			LogError(QString("Bad configuration for bunny '%1', plugin '%2'").arg(QString(b->GetID()), GetName()));
		}
		else
		{
			int day = when.at(0).toInt();
			QString time = when.at(1);
			QString name = i.value().toString();
			if(day == 0)
			{
				Cron::RegisterDaily(this, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(name));
			}
			else
			{
				Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(name));
			}
		}
	}
}

void PluginWebradio::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

bool PluginWebradio::streamWebradio(Bunny * b, QString url)
{
	QByteArray message = "ST "+url.toLatin1()+"\nMW\n";
	b->SendPacket(MessagePacket(message), GetName());
	return true;
}

bool PluginWebradio::streamPresetWebradio(Bunny * b, QString preset)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
	if(list.contains(preset))
	{
		QString url = list.value(preset).toString();
		return streamWebradio(b, url);
	}
	else if(presets.contains(preset))
	{
		QString url = presets.value(preset).toString();
		return streamWebradio(b, url);
	}
	return false;
}

/*******
 * API *
 *******/

void PluginWebradio::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", PluginWebradio, Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("preset()", PluginWebradio, Api_Preset);
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", PluginWebradio, Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("url()", PluginWebradio, Api_Url);
	DECLARE_PLUGIN_API_CALL("preset()", PluginWebradio, Api_PluginPreset);
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_RFID)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		if(!hRequest.HasArg("preset"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("preset", GetName()));

		QString preset = hRequest.GetArg("preset");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, preset);
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

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(list.contains(tag))
		{
			list.remove(tag);
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

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_Schedule)
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

		int day = 0;
		if(hRequest.HasArg("day"))
			day = hRequest.GetArg("day").toInt();

		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(!list.contains(QString::number(day) + "|" + time))
		{
			if(day == 0)
			{
				Cron::RegisterDaily(this, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(name));
			}
			else
			{
				Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(name));
			}

			list.insert(QString::number(day) + "|" + time, name);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			return new ApiManager::ApiOk(Translator::tr("Add schedule at '%1' to bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		if(day == 0)
			return new ApiManager::ApiError(Translator::tr("Schedule at '%1' already exists for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		return new ApiManager::ApiError(Translator::tr("Schedule on '%1', at '%2' already exists for bunny '%3'", account).arg(QString::number(day), time, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

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

			return new ApiManager::ApiOk(Translator::tr("Schedule at '%1' removed for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_Preset)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant>::iterator i;
		for (i = presets.begin(); i != presets.end(); ++i)
			list.insert("OJN_" + i.key(), i.value());

		return new ApiManager::ApiMappedList(list);
	}
	else if(action == "add")
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		list.insert(hRequest.GetArg("name"), hRequest.GetArg("url"));
		bunny->SetPluginSetting(GetName(), "Presets", list);

		return new ApiManager::ApiOk(Translator::tr("Add preset '%1' for radio '%2', bunny '%3'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("url"), QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		if(list.contains(hRequest.GetArg("name")))
			list.remove(hRequest.GetArg("name"));
		else
			return new ApiManager::ApiError(Translator::tr("Cannot remove this default preset", account));
		bunny->SetPluginSetting(GetName(), "Presets", list);

		return new ApiManager::ApiOk(Translator::tr("Remove preset '%1' for bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else if(action == "getdefault")
	{
		QString type = "";
		if(hRequest.HasArg("type"))
			type = hRequest.GetArg("type");

		QString name = bunny->GetPluginSetting(GetName(), "DefaultWebradio", QString()).toString();
		if(type == "second")
			name = bunny->GetPluginSetting(GetName(), "OtherWebradio", QString()).toString();
		if(type == "both")
			name += "|" + bunny->GetPluginSetting(GetName(), "OtherWebradio", QString()).toString();
		return new ApiManager::ApiString(name);
	}
	else if(action == "setdefault")
	{
		QString type = "";
		if(hRequest.HasArg("type"))
			type = hRequest.GetArg("type");

		if(type == "second")
			bunny->SetPluginSetting(GetName(), "OtherWebradio", hRequest.GetArg("name"));
		else
			bunny->SetPluginSetting(GetName(), "DefaultWebradio", hRequest.GetArg("name"));
		return new ApiManager::ApiOk(Translator::tr("Define '%1' preset webradio as default for bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else if(action == "play")
	{
		if(!bunny->IsConnected())
			return new ApiManager::ApiError(Translator::tr("Bunny '%1' is not connected", account).arg(hRequest.GetArg("to")));

		if(streamPresetWebradio(bunny, hRequest.GetArg("name")))
			return new ApiManager::ApiOk(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
		return new ApiManager::ApiError(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_Url)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "play")
	{
		if(!bunny->IsConnected())
			return new ApiManager::ApiError(Translator::tr("Bunny '%1' is not connected", account).arg(QString(bunny->GetID())));

		if(!hRequest.HasArg("url"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("url", GetName()));

		QString url = hRequest.GetArg("url");

		if(streamWebradio(bunny, url))
			return new ApiManager::ApiOk(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(url, QString(bunny->GetID())));

		return new ApiManager::ApiError(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(url, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginWebradio::Api_PluginPreset)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> pluginPresets = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant> list;
		QMap<QString, QVariant>::iterator i;
		for (i = pluginPresets.begin(); i != pluginPresets.end(); ++i)
			list.insert("OJN_" + i.key(), i.value());

		return new ApiManager::ApiMappedList(list);
	}
	else if(action == "add")
	{
		QMap<QString, QVariant> list = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
		list.insert(hRequest.GetArg("name"), hRequest.GetArg("url"));
		SetSettings("Presets", list);
		Init();

		return new ApiManager::ApiOk(Translator::tr("Add preset '%1' for radio '%2'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("url")));
	}
	else if(action == "del")
	{
		QMap<QString, QVariant> list = GetSettings("Presets", QMap<QString, QVariant>()).toMap();

		if(list.contains(hRequest.GetArg("name")))
			list.remove(hRequest.GetArg("name"));
		else
			return new ApiManager::ApiError(Translator::tr("Cannot remove this default preset", account));

		SetSettings("Presets", list);
		Init();

		return new ApiManager::ApiOk(Translator::tr("Remove preset '%1'", account).arg(hRequest.GetArg("name")));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
/*
PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_AddRFID)
{
	Q_UNUSED(account);

	bunny->SetPluginSetting(GetName(), QString("RFIDPlay/%1").arg(hRequest.GetArg("tag")), hRequest.GetArg("name"));

	return new ApiManager::ApiOk(Translator::tr("Add '%1' for RFID '%2', bunny '%3'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_RemoveRFID)
{
	Q_UNUSED(account);

	bunny->RemovePluginSetting(GetName(), QString("RFIDPlay/%1").arg(hRequest.GetArg("tag")));

	return new ApiManager::ApiOk(Translator::tr("Remove RFID '%2' for bunny '%3'", account).arg(hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_AddPreset)
{
	Q_UNUSED(account);

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
	list.insert(hRequest.GetArg("name"), hRequest.GetArg("url"));
	bunny->SetPluginSetting(GetName(), "Presets", list);

	return new ApiManager::ApiOk(Translator::tr("Add preset '%1' for radio '%2', bunny '%3'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("url"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_RemovePreset)
{
	Q_UNUSED(account);

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
	if(list.contains(hRequest.GetArg("name")))
		list.remove(hRequest.GetArg("name"));
	else
		return new ApiManager::ApiError(Translator::tr("Cannot remove this default preset", account));
	bunny->SetPluginSetting(GetName(), "Presets", list);

	return new ApiManager::ApiOk(Translator::tr("Remove preset '%1' for bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_GetDefault)
{
	Q_UNUSED(account);

	QString type = "";
	if(hRequest.HasArg("type"))
		type = hRequest.GetArg("type");

	QString name = bunny->GetPluginSetting(GetName(), "DefaultWebradio", QString()).toString();
	if(type == "second")
		name = bunny->GetPluginSetting(GetName(), "OtherWebradio", QString()).toString();
	if(type == "both")
		name += "|" + bunny->GetPluginSetting(GetName(), "OtherWebradio", QString()).toString();
	return new ApiManager::ApiString(name);
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_SetDefault)
{
	Q_UNUSED(account);

	QString type = "";
	if(hRequest.HasArg("type"))
		type = hRequest.GetArg("type");

	if(type == "second")
		bunny->SetPluginSetting(GetName(), "OtherWebradio", hRequest.GetArg("name"));
	else
		bunny->SetPluginSetting(GetName(), "DefaultWebradio", hRequest.GetArg("name"));
	return new ApiManager::ApiOk(Translator::tr("Define '%1' preset webradio as default for bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_Play)
{
	Q_UNUSED(account);

	if(!bunny->IsConnected())
		return new ApiManager::ApiError(Translator::tr("Bunny '%1' is not connected", account).arg(hRequest.GetArg("to")));

    	if(streamPresetWebradio(bunny, hRequest.GetArg("name")))
    		return new ApiManager::ApiOk(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	return new ApiManager::ApiError(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_PlayUrl)
{
	Q_UNUSED(account);

	if(!bunny->IsConnected())
		return new ApiManager::ApiError(Translator::tr("Bunny '%1' is not connected", account).arg(hRequest.GetArg("to")));

	if(streamWebradio(bunny, hRequest.GetArg("url")))
		return new ApiManager::ApiOk(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(hRequest.GetArg("url"), QString(bunny->GetID())));
	return new ApiManager::ApiError(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(hRequest.GetArg("url"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_AddWebcast)
{
	Q_UNUSED(account);

	QString hTime = hRequest.GetArg("time");
	if(!bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap().contains(hTime))
	{
		Cron::RegisterDaily(this, Cron::mkTime(hTime), bunny, QVariant::fromValue(hRequest.GetArg("name")));
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		list.insert(hTime, hRequest.GetArg("name"));
		bunny->SetPluginSetting(GetName(), "Schedules", list);
		return new ApiManager::ApiOk(Translator::tr("Add schedule at '%1' to bunny '%2'", account).arg(hRequest.GetArg("time"), QString(bunny->GetID())));
	}
	return new ApiManager::ApiError(Translator::tr("Schedule at '%1' already exists for bunny '%2'", account).arg(hRequest.GetArg("time"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_RemoveWebcast)
{
	Q_UNUSED(account);

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	QString time = hRequest.GetArg("time");
	if(list.contains(time))
	{
		list.remove(time);
		bunny->SetPluginSetting(GetName(), "Schedules", list);

		// Recreate crons
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);

		return new ApiManager::ApiOk(Translator::tr("Remove schedule at '%1' for bunny '%2'", account).arg(hRequest.GetArg("time"), QString(bunny->GetID())));
	}
	return new ApiManager::ApiError(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(hRequest.GetArg("time"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_ListWebcast)
{
	Q_UNUSED(account);
	Q_UNUSED(bunny);
	Q_UNUSED(hRequest);

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();

	return new ApiManager::ApiMappedList(list);
}

PLUGIN_BUNNY_API_CALL(PluginWebradio::Api_ListPreset)
{
	Q_UNUSED(account);
	Q_UNUSED(bunny);
	Q_UNUSED(hRequest);

	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
	QMap<QString, QVariant>::iterator i;
	for (i = presets.begin(); i != presets.end(); ++i)
		list.insert("OJN_" + i.key(), i.value());

	return new ApiManager::ApiMappedList(list);
}

PLUGIN_API_CALL(PluginWebradio::Api_ListPluginPreset)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> pluginPresets = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
	QMap<QString, QVariant> list;
	QMap<QString, QVariant>::iterator i;
	for (i = pluginPresets.begin(); i != pluginPresets.end(); ++i)
		list.insert("OJN_" + i.key(), i.value());

	return new ApiManager::ApiMappedList(list);
}

PLUGIN_API_CALL(PluginWebradio::Api_AddPluginPreset)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
	list.insert(hRequest.GetArg("name"), hRequest.GetArg("url"));
	SetSettings("Presets", list);
	Init();

	return new ApiManager::ApiOk(Translator::tr("Add preset '%1' for radio '%2'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("url")));
}

PLUGIN_API_CALL(PluginWebradio::Api_RemovePluginPreset)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
	if(list.contains(hRequest.GetArg("name")))
		list.remove(hRequest.GetArg("name"));
	else
		return new ApiManager::ApiError(Translator::tr("Cannot remove this default preset", account));
	SetSettings("Presets", list);
	Init();

	return new ApiManager::ApiOk(Translator::tr("Remove preset '%1'", account).arg(hRequest.GetArg("name")));
}
*/

