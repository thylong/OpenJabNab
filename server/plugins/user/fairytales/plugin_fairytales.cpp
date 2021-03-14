#include <QMap>
#include <QMapIterator>
#include <QRandomGenerator>
#include <QTime>
#include "plugin_fairytales.h"
#include "account.h"
#include "bunny.h"
#include "cron.h"
#include "bunnymanager.h"
#include "messagepacket.h"
#include "translator.h"

PluginFairytale::PluginFairytale():PluginInterface("fairytales", "Fairy tales and nursery rhymes", BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | ZtampPlugin | CronPlugin | RfidPlugin | VoicePlugin)
{
/*
            "La nuit avant noël":"http://d39g8zahit2xdb.cloudfront.net/nab/contes/stephy-la-nuit-avant-noel-conte-noel-illustre.mp3",
            "La valse des loups":"http://d39g8zahit2xdb.cloudfront.net/nab/contes/stephy-la-valse-des-loups-conte-illustre.mp3",
            "Le petit chaperon rouge":"http://d39g8zahit2xdb.cloudfront.net/nab/contes/stephy-le-petit-chaperon-rouge-conte-illustre.mp3",
            "Le rock de la sorcière":"http://d39g8zahit2xdb.cloudfront.net/nab/contes/stephy-le-rock-de-la-sorciere-conte-illustre.mp3",
            "Le vilain petit canard":"http://d39g8zahit2xdb.cloudfront.net/nab/contes/stephy-le-vilain-petit-canard-conte-illustre.mp3",
            "Petit moustique":"http://d39g8zahit2xdb.cloudfront.net/nab/contes/stephy-petit-moustique-conte-illustre.mp3"
*/
}

PluginFairytale::~PluginFairytale()
{
    Cron::UnregisterAll(this);
}

bool PluginFairytale::Init()
{
	presets.clear();
	presets = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
	return true;
}

void PluginFairytale::OnCron(Bunny * b, QVariant v, unsigned int)
{
	QString name = v.value<QString>();
        streamPresetFairytale(b, name);
}

bool PluginFairytale::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("fairy,tale", b), command))
	{
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant>::iterator i;
		int max = 0;
		QString key = "";
		for (i = list.begin(); i != list.end(); ++i)
		{
			int p = getPertinence(i.key(), command);
			if( p > max )
			{
				max = p;
				key = i.key();
			}
		}
		QMap<QString, QVariant>::iterator j;
		for (j = presets.begin(); j != presets.end(); ++j)
		{
			int p = getPertinence(j.key(), command);
			if( p > max )
			{
				max = p;
				key = j.key();
			}
		}
		if(max > 0)
		{
			return streamPresetFairytale(b, key);
		}
		else
		{
			QString fairytale = b->GetPluginSetting(GetName(), "DefaultFairytale", QString()).toString();
			return streamPresetFairytale(b, fairytale);
		}
	}
	return false;
}

bool PluginFairytale::OnRFID(Ztamp * z, Bunny * b)
{
	QString fairytale = z->GetPluginSetting(GetName(), QString("Play"), QString()).toString();
	if(fairytale != "")
	{
		return streamPresetFairytale(b, fairytale);
	}
	return false;
}

bool PluginFairytale::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
        	return streamPresetFairytale(b, list.value(rfid).toString());
	}
	return false;
}

bool PluginFairytale::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	QString fairytale = "";
	if(type == PluginInterface::SingleClick)
	{
		fairytale = b->GetPluginSetting(GetName(), "DefaultFairytale", QString()).toString();
	}
	else if (type == PluginInterface::DoubleClick)
	{
		fairytale = b->GetPluginSetting(GetName(), "OtherFairytale", b->GetPluginSetting(GetName(), "DefaultFairytale", QString()).toString()).toString();
	}
	if(fairytale.length())
	{
		if(streamPresetFairytale(b, fairytale))
			return true;
		return false;
	}
	return false;
}

void PluginFairytale::OnBunnyConnect(Bunny * b)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();

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

void PluginFairytale::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

bool PluginFairytale::streamFairytale(Bunny * b, QString url)
{
	QByteArray message = "ST "+url.toLatin1()+"\nMW\n";
	b->SendPacket(MessagePacket(message), GetName());
	return true;
}

bool PluginFairytale::streamPresetFairytale(Bunny * b, QString preset)
{
	if(preset == "RANDOM_ALL")
	{
		return streamRandomFairytale(b, true, true);
	}
	else if(preset == "RANDOM_PLUGIN")
	{
		return streamRandomFairytale(b, true, false);
	}
	else if(preset == "RANDOM_OWNER")
	{
		return streamRandomFairytale(b, false, true);
	}
	else
	{
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		if(list.contains(preset))
		{
			QString url = list.value(preset).toString();
			return streamFairytale(b, url);
		}
		else if(presets.contains(preset))
		{
			QString url = presets.value(preset).toString();
			return streamFairytale(b, url);
		}
		return false;
	}
}

bool PluginFairytale::streamRandomFairytale(Bunny * b, bool plugin, bool owner)
{
	QStringList list;
	if(plugin)
	{
		QMapIterator<QString, QVariant> i(presets);
		while (i.hasNext())
		{
			i.next();
			list.append(i.key());
		}
	}
	if(owner)
	{
		QMapIterator<QString, QVariant> i(b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap());
		while (i.hasNext())
		{
			i.next();
			list.append(i.key());
		}
	}
	if(list.count())
	{
		QString random = list.at(QRandomGenerator::global()->generate() % list.count());
		return streamPresetFairytale(b, random);
	}
	return false;
}

/*******
 * API *
 *******/

void PluginFairytale::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginFairytale::Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("preset()", &PluginFairytale::Api_Preset);
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginFairytale::Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("url()", &PluginFairytale::Api_Url);
	DECLARE_PLUGIN_API_CALL("preset()", &PluginFairytale::Api_PluginPreset);
}

PLUGIN_BUNNY_API_CALL(PluginFairytale::Api_RFID)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		if(!hRequest.HasArg("preset"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("preset", GetName()));

		QString preset = hRequest.GetArg("preset");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, preset);
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

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(list.contains(tag))
		{
			list.remove(tag);
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

PLUGIN_BUNNY_API_CALL(PluginFairytale::Api_Schedule)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("time"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		int day = 0;
		if(hRequest.HasArg("day"))
			day = hRequest.GetArg("day").toInt();

		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

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

PLUGIN_BUNNY_API_CALL(PluginFairytale::Api_Preset)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant>::iterator i;
		for (i = presets.begin(); i != presets.end(); ++i)
			list.insert("OJN_" + i.key(), i.value());

		return new ApiAnswers::MappedList(list);
	}
	else if(action == "add")
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		list.insert(hRequest.GetArg("name"), hRequest.GetArg("url"));
		bunny->SetPluginSetting(GetName(), "Presets", list);

		return new ApiAnswers::Ok(Translator::tr("Add preset '%1' for fairytale '%2', bunny '%3'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("url"), QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		if(list.contains(hRequest.GetArg("name")))
			list.remove(hRequest.GetArg("name"));
		else
			return new ApiAnswers::Error(Translator::tr("Cannot remove this default preset", account));
		bunny->SetPluginSetting(GetName(), "Presets", list);

		return new ApiAnswers::Ok(Translator::tr("Remove preset '%1' for bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else if(action == "getdefault")
	{
		QString type = "";
		if(hRequest.HasArg("type"))
			type = hRequest.GetArg("type");

		QString name = bunny->GetPluginSetting(GetName(), "DefaultFairytale", QString()).toString();
		if(type == "second")
			name = bunny->GetPluginSetting(GetName(), "OtherFairytale", QString()).toString();
		if(type == "both")
			name += "|" + bunny->GetPluginSetting(GetName(), "OtherFairytale", QString()).toString();
		return new ApiAnswers::String(name);
	}
	else if(action == "setdefault")
	{
		QString type = "";
		if(hRequest.HasArg("type"))
			type = hRequest.GetArg("type");

		if(type == "second")
			bunny->SetPluginSetting(GetName(), "OtherFairytale", hRequest.GetArg("name"));
		else
			bunny->SetPluginSetting(GetName(), "DefaultFairytale", hRequest.GetArg("name"));
		return new ApiAnswers::Ok(Translator::tr("Define '%1' preset fairytales as default for bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else if(action == "play")
	{
		if(!bunny->IsConnected())
			return new ApiAnswers::Error(Translator::tr("Bunny '%1' is not connected", account).arg(hRequest.GetArg("to")));

		if(streamPresetFairytale(bunny, hRequest.GetArg("name")))
			return new ApiAnswers::Ok(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
		return new ApiAnswers::Error(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginFairytale::Api_Url)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "play")
	{
		if(!bunny->IsConnected())
			return new ApiAnswers::Error(Translator::tr("Bunny '%1' is not connected", account).arg(QString(bunny->GetID())));

		if(!hRequest.HasArg("url"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("url", GetName()));

		QString url = hRequest.GetArg("url");

		if(streamFairytale(bunny, url))
			return new ApiAnswers::Ok(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(url, QString(bunny->GetID())));

		return new ApiAnswers::Error(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(url, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginFairytale::Api_PluginPreset)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> pluginPresets = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant> list;
		QMap<QString, QVariant>::iterator i;
		for (i = pluginPresets.begin(); i != pluginPresets.end(); ++i)
			list.insert("OJN_" + i.key(), i.value());

		return new ApiAnswers::MappedList(list);
	}
	else if(action == "add")
	{
		QMap<QString, QVariant> list = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
		list.insert(hRequest.GetArg("name"), hRequest.GetArg("url"));
		SetSettings("Presets", list);
		Init();

		return new ApiAnswers::Ok(Translator::tr("Add preset '%1' for fairytale '%2'", account).arg(hRequest.GetArg("name"), hRequest.GetArg("url")));
	}
	else if(action == "del")
	{
		QMap<QString, QVariant> list = GetSettings("Presets", QMap<QString, QVariant>()).toMap();

		if(list.contains(hRequest.GetArg("name")))
			list.remove(hRequest.GetArg("name"));
		else
			return new ApiAnswers::Error(Translator::tr("Cannot remove this default preset", account));

		SetSettings("Presets", list);
		Init();

		return new ApiAnswers::Ok(Translator::tr("Remove preset '%1'", account).arg(hRequest.GetArg("name")));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
