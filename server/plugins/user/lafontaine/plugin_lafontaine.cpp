#include <QMap>
#include <QMapIterator>
#include <QTime>
#include <QRandomGenerator>
#include "plugin_lafontaine.h"
#include "account.h"
#include "bunny.h"
#include "cron.h"
#include "bunnymanager.h"
#include "messagepacket.h"
#include "translator.h"

/*
http://www.arena80.it/pagine/fiabesonore.asp
*/

PluginLafontaine::PluginLafontaine():PluginInterface("lafontaine", "Fairy tales and nursery rhymes", BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | ZtampPlugin | CronPlugin | RfidPlugin | VoicePlugin)
{
/*
La cigale et la fourmi => STE-001.mp3
Le corbeau et le renard => STE-002.mp3
La grenouille qui veut se faire aussi grosse que le b&oelig;uf => STE-003.mp3
Les deux mulets => STE-004.mp3
Le loup et le chien => STE-005.mp3
La besace => STE-006.mp3
Le rat de ville et le rat des champs => STE-007.mp3
Le loup et l&rsquo;agneau => STE-008.mp3
La mort et le bûcheron => STE-009.mp3
Le renard et la cigogne => STE-010.mp3
Le chêne et le roseau => STE-011.mp3
L&rsquo;âne chargé d&rsquo;éponge et l&rsquo;âne chargé de sel => STE-012.mp3
Le lion et le rat - La colombe et la fourmi => STE-013.mp3
Le lièvre et les grenouilles => STE-014.mp3
Le paon se plaignant à Junon => STE-015.mp3
La chatte métamorphosée en femme => STE-016.mp3
Le renard et le bouc => STE-017.mp3
Le renard et les raisins => STE-018.mp3
Le jardinier et son seigneur => STE-019.mp3
Le singe et le dauphin => STE-020.mp3
Le pot de terre et le pot de fer => STE-021.mp3
Le laboureur et ses enfants => STE-022.mp3
La montagne qui accouche&hellip; => STE-023.mp3
Le cochet, le chat et le souriceau => STE-024.mp3
Le lièvre et la tortue => STE-025.mp3
Le chien qui laisse sa proie pour l&rsquo;ombre => STE-026.mp3
La jeune veuve => STE-027.mp3
Les animaux malades de la peste => STE-028.mp3
Le héron => STE-029.mp3
La fille => STE-030.mp3
La cour du lion => STE-031.mp3
Le coche et la mouche => STE-032.mp3
La laitière et le pot au lait => STE-033.mp3
Le chat, la belette et le petit lapin => STE-034.mp3
Le savetier et le financier => STE-035.mp3
Les deux amis => STE-037.mp3
Le rat et l&rsquo;éléphant => STE-038.mp3
Les deux pigeons => STE-039.mp3
L&rsquo;huître et les plaideurs => STE-040.mp3
Le vieillard et les trois jeunes hommes => STE-041.mp3
Les compagnons d&rsquo;Ulysse => STE-042.mp3

*/
}

PluginLafontaine::~PluginLafontaine()
{
    Cron::UnregisterAll(this);
}

bool PluginLafontaine::Init()
{
	presets.clear();
	presets = GetSettings("Presets", QMap<QString, QVariant>()).toMap();
	return true;
}

void PluginLafontaine::OnCron(Bunny * b, QVariant v, unsigned int)
{
	QString name = v.value<QString>();
        streamPresetLafontaine(b, name);
}

bool PluginLafontaine::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
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
			return streamPresetLafontaine(b, key);
		}
		else
		{
			QString fairytale = b->GetPluginSetting(GetName(), "DefaultLafontaine", QString()).toString();
			return streamPresetLafontaine(b, fairytale);
		}
	}
	return false;
}

bool PluginLafontaine::OnRFID(Ztamp * z, Bunny * b)
{
	QString fairytale = z->GetPluginSetting(GetName(), QString("Play"), QString()).toString();
	if(fairytale != "")
	{
		return streamPresetLafontaine(b, fairytale);
	}
	return false;
}

bool PluginLafontaine::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
        	return streamPresetLafontaine(b, list.value(rfid).toString());
	}
	return false;
}

bool PluginLafontaine::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	QString fairytale = "";
	if(type == PluginInterface::SingleClick)
	{
		fairytale = b->GetPluginSetting(GetName(), "DefaultLafontaine", QString()).toString();
	}
	else if (type == PluginInterface::DoubleClick)
	{
		fairytale = b->GetPluginSetting(GetName(), "OtherLafontaine", b->GetPluginSetting(GetName(), "DefaultLafontaine", QString()).toString()).toString();
	}
	if(fairytale.length())
	{
		if(streamPresetLafontaine(b, fairytale))
			return true;
		return false;
	}
	return false;
}

void PluginLafontaine::OnBunnyConnect(Bunny * b)
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

void PluginLafontaine::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

bool PluginLafontaine::streamLafontaine(Bunny * b, QString url)
{
	QByteArray message = "ST "+url.toLatin1()+"\nMW\n";
	b->SendPacket(MessagePacket(message), GetName());
	return true;
}

bool PluginLafontaine::streamPresetLafontaine(Bunny * b, QString preset)
{
	if(preset == "RANDOM_ALL")
	{
		return streamRandomLafontaine(b, true, true);
	}
	else if(preset == "RANDOM_PLUGIN")
	{
		return streamRandomLafontaine(b, true, false);
	}
	else if(preset == "RANDOM_OWNER")
	{
		return streamRandomLafontaine(b, false, true);
	}
	else
	{
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		if(list.contains(preset))
		{
			QString url = list.value(preset).toString();
			return streamLafontaine(b, url);
		}
		else if(presets.contains(preset))
		{
			QString url = presets.value(preset).toString();
			return streamLafontaine(b, url);
		}
		return false;
	}
}

bool PluginLafontaine::streamRandomLafontaine(Bunny * b, bool plugin, bool owner)
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
		return streamPresetLafontaine(b, random);
	}
	return false;
}

/*******
 * API *
 *******/

void PluginLafontaine::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginLafontaine::Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("preset()", &PluginLafontaine::Api_Preset);
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginLafontaine::Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("url()", &PluginLafontaine::Api_Url);
	DECLARE_PLUGIN_API_CALL("preset()", &PluginLafontaine::Api_PluginPreset);
}

PLUGIN_BUNNY_API_CALL(PluginLafontaine::Api_RFID)
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

PLUGIN_BUNNY_API_CALL(PluginLafontaine::Api_Schedule)
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

PLUGIN_BUNNY_API_CALL(PluginLafontaine::Api_Preset)
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

		QString name = bunny->GetPluginSetting(GetName(), "DefaultLafontaine", QString()).toString();
		if(type == "second")
			name = bunny->GetPluginSetting(GetName(), "OtherLafontaine", QString()).toString();
		if(type == "both")
			name += "|" + bunny->GetPluginSetting(GetName(), "OtherLafontaine", QString()).toString();
		return new ApiAnswers::String(name);
	}
	else if(action == "setdefault")
	{
		QString type = "";
		if(hRequest.HasArg("type"))
			type = hRequest.GetArg("type");

		if(type == "second")
			bunny->SetPluginSetting(GetName(), "OtherLafontaine", hRequest.GetArg("name"));
		else
			bunny->SetPluginSetting(GetName(), "DefaultLafontaine", hRequest.GetArg("name"));
		return new ApiAnswers::Ok(Translator::tr("Define '%1' preset lafontaine as default for bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else if(action == "play")
	{
		if(!bunny->IsConnected())
			return new ApiAnswers::Error(Translator::tr("Bunny '%1' is not connected", account).arg(hRequest.GetArg("to")));

		if(streamPresetLafontaine(bunny, hRequest.GetArg("name")))
			return new ApiAnswers::Ok(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
		return new ApiAnswers::Error(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(hRequest.GetArg("name"), QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginLafontaine::Api_Url)
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

		if(streamLafontaine(bunny, url))
			return new ApiAnswers::Ok(Translator::tr("Now streaming '%1' on bunny '%2'", account).arg(url, QString(bunny->GetID())));

		return new ApiAnswers::Error(Translator::tr("Can't stream '%1' on bunny '%2'", account).arg(url, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginLafontaine::Api_PluginPreset)
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
