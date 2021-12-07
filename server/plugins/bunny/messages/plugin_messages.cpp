#include <QDateTime>
#include <QCryptographicHash>
#include <QXmlStreamReader>
#include <QMapIterator>
#include <QRegExp>
#include <QUrl>
#include <memory>

#include "plugin_messages.h"

#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "httprequest.h"
#include "log.h"
#include "packets/ambientpacket.h"
#include "packets/messagepacket.h"
#include "pluginmanager.h"
#include "settings.h"
#include "tts/ttsmanager.h"
#include "translator.h"
#include "ztampmanager.h"

PluginMessages::PluginMessages()
	: PluginInterface("messages", "Repeat previous messages", BunnyV2Plugin | RfidPlugin | SingleClickPlugin | DoubleClickPlugin | VoicePlugin | CronPlugin )
{
	PluginStateChanged();
}

void PluginMessages::MessageNotification(Bunny * b)
{
	int pendingMessages = CountMessages(b);
	unsigned char value;
	if(pendingMessages == 0)
		value = AmbientPacket::Nose_No;
	else if(pendingMessages == 1)
		value = AmbientPacket::Nose_Blink;
	else
		value = AmbientPacket::Nose_DoubleBlink;
	b->SendPacket(AmbientPacket(AmbientPacket::Service_Nose, value), GetName());
}

bool PluginMessages::AddMessage(Bunny * b, QString plugin, QStringList files, int)
{
	QStringList list = GetMessages(b);
	if(GetKeepTime(b, plugin))
	{
		QString message = QString::number(QDateTime::currentDateTime().toTime_t()) + "|" + plugin + "|" + files.join("|");
		list.append(message);
		return SaveMessages(b, list);
	}
	return false;
}

bool PluginMessages::AddMessage(Bunny * b, QString plugin, QStringList files)
{
	QStringList list = GetMessages(b);
	if(GetKeepTime(b, plugin))
	{
		QString message = QString::number(QDateTime::currentDateTime().toTime_t()) + "|" + plugin + "|" + files.join("|");
		list.append(message);
		return SaveMessages(b, list);
	}
	return false;
}

bool PluginMessages::SaveMessages(Bunny * b, QStringList list)
{
	b->SetPluginSetting(GetName(), "Messages", list);
	MessageNotification(b);
	return true;
}

QStringList PluginMessages::GetMessages(Bunny * b)
{
	return b->GetPluginSetting(GetName(), "Messages", QStringList()).toStringList();
}

int PluginMessages::GetKeepTime(Bunny * b)
{
	return GetKeepTime(b, "default");
}

int PluginMessages::GetKeepTime(Bunny * b, QString plugin)
{
	return b->GetPluginSetting(GetName(), QString("Keep/%1").arg(plugin), b->GetPluginSetting(GetName(), "Keep/default", 0).toInt()).toInt();
}

void PluginMessages::ClearMessages(Bunny * b)
{
	b->RemovePluginSetting(GetName(), "Messages");
	MessageNotification(b);
}

bool PluginMessages::RemoveMessage(Bunny * b, int id)
{
	if(id < CountMessages(b))
	{
		QStringList list = GetMessages(b);
		list.removeAt(id);
		b->SetPluginSetting(GetName(), "Messages", list);
		return true;
	}
	return false;
}

void PluginMessages::CleanMessages(Bunny * b)
{
	QDateTime now = QDateTime::currentDateTime();
	QStringList list = GetMessages(b);
	foreach(QString message, list)
	{
		QStringList parts = message.split("|");
		if(QDateTime::fromTime_t(parts.at(0).toInt()).addSecs(3600 * GetKeepTime(b, parts.at(1))) <= now)
		{
			list.removeAll(message);
		}
	}
	b->SetPluginSetting(GetName(), "Messages", list);
}

void PluginMessages::CleanMessages(Bunny * b, QString plugin)
{
	QDateTime now = QDateTime::currentDateTime();
	QStringList list = GetMessages(b);
	foreach(QString message, list)
	{
		if(getPlugin(message) == plugin)
		{
			list.removeAll(message);
		}
	}
	b->SetPluginSetting(GetName(), "Messages", list);
}

void PluginMessages::CleanMessages(Bunny * b, int max)
{
	QDateTime now = QDateTime::currentDateTime();
	QStringList list = GetMessages(b);
	foreach(QString message, list)
	{
		QStringList parts = message.split("|");
		if(QDateTime::fromTime_t(parts.at(0).toInt()).addSecs(3600 * GetKeepTime(b, parts.at(1))) <= now)
		{
			list.removeAll(message);
		}
	}
	int size = list.count();
	if(size > max)
	{
		list = list.mid(size - max, max);
	}
	b->SetPluginSetting(GetName(), "Messages", list);
}

void PluginMessages::CleanMessages(Bunny * b, QString plugin, int max)
{
	int count = CountMessages(b, plugin);
	if(count > max)
	{
		if(max == 0)
		{
			CleanMessages(b, plugin);
		}
		else
		{
			QStringList list = GetMessages(b);
			count = 0;
			for(int i=list.count() - 1; i>= 0; i--)
			{
				QString message = list.at(i);
				if(getPlugin(message) == plugin)
				{
					if(count++ >= max)
					{
						list.removeAll(message);
					}
				}
			}
			b->SetPluginSetting(GetName(), "Messages", list);
		}
	}
}

void PluginMessages::PluginStateChanged()
{
	if(GetEnable())
	{
		Cron::Register(this, 10, 0, 0, NULL, Cron::Classic);
	}
	else
	{
		Cron::UnregisterAll(this);
	}
}

QString PluginMessages::getPlugin(QString message)
{
	QStringList parts = message.split("|");
	return parts.count() >= 2 ? parts.at(1) : "";
}

int PluginMessages::CountMessages(Bunny * b)
{
	CleanMessages(b);
	return GetMessages(b).count();
}

int PluginMessages::CountMessages(Bunny * b, QString plugin)
{
	CleanMessages(b);
	int count = 0;
	QStringList list = GetMessages(b);
	foreach(QString message, list)
	{
		if(getPlugin(message) == plugin)
		{
			count++;
		}
	}
	return count;
}

void PluginMessages::OnCron(Bunny * b, QVariant v, unsigned int)
{
	if(b == NULL)
	{
		//Log::LogInfo("Cleaning messages");
		foreach(QByteArray id, BunnyManager::GetConnectedBunniesList())
		{
			Bunny * bunny = BunnyManager::GetBunny(this, id);
			if(bunny)
			{
				//Log::LogInfo("Cleaning messages for " + bunny->GetBunnyName());
				CleanMessages(bunny);
			}
		}

		//Log::LogInfo("finish cleaning messages");
	}
	else
	{
		QString option = v.toString();
		if(option == "clear")
		{
			ClearMessages(b);
		}
		else
		{
			sayMessages(b, option);
		}
	}
}

bool PluginMessages::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("last,message", b), command))
	{
		sayMessages(b);
		return true;
	}
	return false;
}

bool PluginMessages::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	if (type == PluginInterface::SingleClick)
	{
		sayMessages(b);
		return true;
	}
	else if (type == PluginInterface::DoubleClick)
	{
		ClearMessages(b);
		return true;
	}
	return false;
}

bool PluginMessages::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		QString option = list.value(rfid).toString();
		if(option == "clear")
		{
			ClearMessages(b);
		}
		else
		{
			sayMessages(b, option);
		}
		return true;
	}
	return false;
}

void PluginMessages::sayMessages(Bunny * b, QString options)
{
	CleanMessages(b);

	if(options == "count")
	{
		QString string = Translator::tr("You have %1 previous messages", b).arg(CountMessages(b));
		TTSAnswer msg = TTSManager::CreateSound(string, b->GetVoice(), b->GetLanguage());
		QByteArray message = "MU " + msg.file.toLatin1() + "\nMW\n";
		TTSLog(b->GetID(), GetName(), msg);
		b->SendPacket(MessagePacket(message), GetName());
	}
	else if(options == "all")
	{
		QStringList list = GetMessages(b);
		while(list.count())
		{
			QString p = list.takeFirst();
			QStringList previous = p.split("|");
			QByteArray message;
			for(int i=2; i<previous.count();i++)
			{
				message += "MU " + previous.at(i) + "\nMW\n";
			}
			if(message.length())
			{
				b->SendPacket(MessagePacket(message), GetName());
			}
		}
		ClearMessages(b);
	}
	else
	{
		QStringList list = GetMessages(b);
		if(list.count())
		{
			QString p = list.takeFirst();
			if(p != "")
			{
				QStringList previous = p.split("|");
				QByteArray message;
				for(int i=2; i<previous.count();i++)
				{
					message += "MU " + previous.at(i) + "\nMW\n";
				}
				if(message.length())
				{
					b->SendPacket(MessagePacket(message), GetName());
				}
			}
			SaveMessages(b, list);
		}
	}
	MessageNotification(b);
}

void PluginMessages::sayMessages(Bunny * b)
{
	sayMessages(b, "");
}

void PluginMessages::OnInitPacket(const Bunny * b, AmbientPacket & a, SleepPacket &)
{
	int pendingMessages = CountMessages(BunnyManager::GetBunny(b->GetID()));
	unsigned char value;
	if(pendingMessages == 0)
		value = AmbientPacket::Nose_No;
	else if(pendingMessages == 1)
		value = AmbientPacket::Nose_Blink;
	else
		value = AmbientPacket::Nose_DoubleBlink;
	a.SetServiceValue(AmbientPacket::Service_Nose, value);
}

void PluginMessages::OnBunnyConnect(Bunny * b)
{
	//ClearMessages(b);

	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext())
	{
		i.next();
		QStringList when = i.key().split("|");
		if(when.count() != 2)
		{
			LogError(QString("Bad configuration for bunny '%1', plugin '%2'").arg(QString(b->GetID()), "messages"));
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

void PluginMessages::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

/*******
 * API *
 *******/

void PluginMessages::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginMessages::Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("option()", &PluginMessages::Api_Option);
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginMessages::Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("message()", &PluginMessages::Api_Message);
	DECLARE_PLUGIN_API_CALL("config()", &PluginMessages::Api_Config);
}

PLUGIN_BUNNY_API_CALL(PluginMessages::Api_Schedule)
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

		QString option = "";
		if(hRequest.HasArg("option"))
			option = hRequest.GetArg("option");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(!list.contains(QString::number(day) + "|" + time))
		{
			if(day == 0)
			{
				Cron::RegisterDaily(this, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(option));
			}
			else
			{
				Cron::RegisterWeekly(this, (Qt::DayOfWeek)day, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(option));
			}

			list.insert(QString::number(day) + "|" + time, option);
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

PLUGIN_BUNNY_API_CALL(PluginMessages::Api_Message)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "count")
	{
		return new ApiAnswers::String(QString::number(CountMessages(bunny)));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("id"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("id", GetName()));

		int id = hRequest.GetArg("id").toInt();
		if(RemoveMessage(bunny, id))
			return new ApiAnswers::Ok(Translator::tr("Message removed", account));
		return new ApiAnswers::Error(Translator::tr("Can't remove message", account));
	}
	else if(action == "clear")
	{
		return new ApiAnswers::Ok(Translator::tr("Messages cleared", account));
	}
	else if(action == "list")
	{
		return new ApiAnswers::List(GetMessages(bunny));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginMessages::Api_Config)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "max")
	{
		QString status = "User";
		if(hRequest.HasArg("status"))
			status = hRequest.GetArg("status");

		if(hRequest.HasArg("set"))
		{
			QString set = hRequest.GetArg("set");
			SetSettings("Keep/" + status, set);

			return new ApiAnswers::Ok(Translator::tr("%1 defined for %2 '%3'", account).arg(Translator::tr("Max keep time", account), Translator::tr("the status", account), status));
		}
		else
		{
			return new ApiAnswers::String(GetSettings("Keep/" + status, 0).toString());
		}
	}
	else if(action == "activate")
	{
		QList<QByteArray> listB =  BunnyManager::GetConnectedBunniesList();

		QMap<QString, QVariant> list;
		foreach(QByteArray id, listB)
		{
			Bunny * b = BunnyManager::GetBunny(id);
			b->AddPlugin(this);
			if(b->GetPluginSetting(GetName(), "Keep/" + GetName(), 0).toInt() == 0)
			{
				b->SetPluginSetting(GetName(), "Keep/" + GetName(), 48);
			}
		}
		return new ApiAnswers::Ok(Translator::tr("Plugin '%1' activated for all connected bunnies", account).arg(GetName()));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginMessages::Api_Option)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "default")
	{
		if(hRequest.HasArg("set"))
		{
			QString set = hRequest.GetArg("set");
			bunny->SetPluginSetting(GetName(), "Default", set);

			if(set == "all")
				return new ApiAnswers::Ok(Translator::tr("Bunny '%1' will say all messages", account).arg(QString(bunny->GetID())));
			else
				return new ApiAnswers::Ok(Translator::tr("Bunny '%1' will say messages one by one", account).arg(QString(bunny->GetID())));
		}
		else
		{
			return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "Default", QString()).toString());
		}
	}
	else if(action == "keep")
	{
		if(hRequest.HasArg("set"))
		{
			int set = hRequest.GetArg("set").toInt();

			if(set < 0)
				return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("set", GetName()));

			if(account.IsAdmin())
			{
				if(set > GetSettings("Keep/Admin", 24 * 14).toInt())
					return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("set", GetName()));
			}
			else if(account.IsVip() || account.IsPremium())
			{
				if(set > GetSettings("Keep/Premium", 24 * 7).toInt())
					return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("set", GetName()));
			}
			else
			{
				if(set > GetSettings("Keep/User", 12).toInt())
					return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("set", GetName()));
			}

			QString plugin = "default";
			if(hRequest.HasArg("plugin"))
				plugin = hRequest.GetArg("plugin");

			bunny->SetPluginSetting(GetName(), QString("Keep/%1").arg(plugin), set);

			if(plugin != "default")
			{
				QStringList list = bunny->GetPluginSetting(GetName(), "Keep/List", QStringList()).toStringList();
				list.append(plugin);
				list.removeDuplicates();
				bunny->SetPluginSetting(GetName(), "Keep/List", list);
			}

			if(set == 0)
			{
				if(plugin != "default")
					return new ApiAnswers::Ok(Translator::tr("Don't keep '%1' messages for bunny '%2'", account).arg(plugin, QString(bunny->GetID())));
				return new ApiAnswers::Ok(Translator::tr("Don't keep messages for bunny '%1'", account).arg(QString(bunny->GetID())));
			}
			else
			{
				if(plugin != "default")
					return new ApiAnswers::Ok(Translator::tr("Keep '%2' messages %1 hours for bunny '%3'", account).arg(QString::number(set), plugin, QString(bunny->GetID())));
				return new ApiAnswers::Ok(Translator::tr("Keep messages %1 hours for bunny '%2'", account).arg(QString::number(set), QString(bunny->GetID())));
			}
		}
		else
		{
			QString plugin = "default";
			if(hRequest.HasArg("plugin"))
				plugin = hRequest.GetArg("plugin");

			return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), QString("Keep/%1").arg(plugin), bunny->GetPluginSetting(GetName(), "Keep/default", "0").toString()).toString());
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginMessages::Api_RFID)
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

		QString option = "";
		if(hRequest.HasArg("option"))
			option = hRequest.GetArg("option");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, option);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Associate(bunny, this);
			return new ApiAnswers::Ok(Translator::tr("Add RFID '%1' for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
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
