#include <memory>

#include <QDateTime>
#include <QMapIterator>

#include "plugin_callurl.h"

#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "log.h"
#include "packets/messagepacket.h"
#include "settings.h"
#include "translator.h"

PluginCallURL::PluginCallURL()
	: PluginInterface("callurl", "Plugin to call an URL",
										BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | CronPlugin | RfidPlugin | EarsPlugin
									 )
{
}

PluginCallURL::~PluginCallURL()
{
	Cron::UnregisterAll(this);
}

QString PluginCallURL::GetURL(Bunny * b, QString name)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
	if(name == "")
	{
		return "";
	}
	if(!list.contains(name))
	{
		LogError(QString("No url named '%1' for bunny '%2'").arg(name, QString(b->GetID())));
		return "";
	}
	return list.value(name).toString();
}

void PluginCallURL::CallURL(Bunny * b, QString url)
{
	url = url.replace("BUNNYMAC", QString(b->GetID())).replace("LEFTPOS", "").replace("RIGHTPOS", "").replace("ZTAMPSN", "").replace("CLICTYPE", "").replace("VOICECMD", "");
	if(url.trimmed().length())
	{
		QByteArray message = "CU " + url.toLatin1() + "\n";
		b->SendPacket(MessagePacket(message), GetName());
	}
	else
	{
		LogError(QString("No URL to send to bunny '%1'").arg(QString(b->GetID())));
	}
}

bool PluginCallURL::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	LogDebug(QString("Call url voice command %1 for bunny %2").arg(command, QString(b->GetID())));
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Voice", QMap<QString, QVariant>()).toMap();
	LogDebug("Voice command with contains");
	if(list.contains(command))
	{
		QString url = GetURL(b, list.value(command).toString());
		url = url.replace("VOICECMD", command);
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnVoiceCommand, command %1").arg(command)));
		CallURL(b, url);
		return true;
	}
	LogDebug("Voice command with foreach and getPertinence");
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext())
	{
		i.next();
		QString cmd = i.key();
		if(getPertinence(cmd, command))
		{
			QString url = GetURL(b, i.value().toString());
			url = url.replace("VOICECMD", command);
			PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnVoiceCommand, command %1").arg(command)));
			CallURL(b, url);
			return true;
		}
	}
/*
	if(list.contains(command))
	{
		QString url = GetURL(b, list.value(command).toString());
		url = url.replace("VOICECMD", command);
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnVoiceCommand, command %1").arg(command)));
		CallURL(b, url);
		return true;
	}
*/
	LogDebug("Voice command with foreach and getPertinence");

	QString defaultUrl = b->GetPluginSetting(GetName(), "Default/Voice", QString()).toString().replace("VOICECMD", command).trimmed();
	if( defaultUrl.length())
	{
		QString url = GetURL(b, defaultUrl).replace("VOICECMD", command);
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), "OnVoiceCommand default"));
		CallURL(b, url);
		return true;
	}
	return false;
}

void PluginCallURL::OnCron(Bunny * b, QVariant v, unsigned int)
{
	QString name = v.value<QString>();
	PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnCron, parameter %1").arg(name)));
	CallURL(b, GetURL(b, name));
}

bool PluginCallURL::OnEarsMove(Bunny * b, int left, int right)
{
	QString leftUrl = b->GetPluginSetting(GetName(), QString("EarLeft/%1").arg(QString::number(left)), QString()).toString();
	QString rightUrl = b->GetPluginSetting(GetName(), QString("EarRight/%1").arg(QString::number(right)), QString()).toString();
	if(leftUrl != QString() && b->GetGlobalSetting("EarLeft", -1).toInt() != left)
	{
		leftUrl = GetURL(b, leftUrl).replace("LEFTPOS", QString::number(left));
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnEarsMove, left ear %1").arg(QString::number(left))));
		CallURL(b, leftUrl);
		return true;
	}
	if(rightUrl != QString() && b->GetGlobalSetting("EarRight", -1).toInt() != right)
	{
		rightUrl = GetURL(b, rightUrl).replace("RIGHTPOS", QString::number(right));
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnEarsMove, right ear %1").arg(QString::number(right))));
		CallURL(b, rightUrl);
		return true;
	}
	if(b->GetGlobalSetting("EarLeft", -1).toInt() != left)
	{
		QString defaultUrl = GetURL(b, b->GetPluginSetting(GetName(), "Default/LeftEar", QString()).toString()).replace("LEFTPOS", QString::number(left)).replace("RIGHTPOS", QString::number(right)).trimmed();
		if( defaultUrl.length())
		{
			PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnEarsMove, left ear %1").arg("default")));
			CallURL(b, defaultUrl);
			return true;
		}
	}
	if(b->GetGlobalSetting("EarRight", -1).toInt() != right)
	{
		QString defaultUrl = GetURL(b, b->GetPluginSetting(GetName(), "Default/RightEar", QString()).toString()).replace("LEFTPOS", QString::number(left)).replace("RIGHTPOS", QString::number(right)).trimmed();
		if( defaultUrl.length())
		{
			PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnEarsMove, right ear %1").arg("default")));
			CallURL(b, defaultUrl);
			return true;
		}
	}
	return false;
}

bool PluginCallURL::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		QString url = GetURL(b, list.value(rfid).toString());
		url = url.replace("ZTAMPSN", QString(tag.toHex()));
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnRFID, tag %1").arg(QString(tag.toHex()))));
		CallURL(b, url);
		return true;
	}
	QString defaultUrl = GetURL(b, b->GetPluginSetting(GetName(), "Default/RFID", QString()).toString()).replace("ZTAMPSN", QString(tag.toHex())).trimmed();
	if( defaultUrl.length())
	{
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnRFID, tag %1").arg("default")));
		CallURL(b, defaultUrl);
		return true;
	}
	return false;
}

bool PluginCallURL::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	QString url = GetURL(b, b->GetPluginSetting(GetName(), "Default/Click", QString("")).toString());
	if(url.trimmed() != QString(""))
	{
		url = url.replace("CLICTYPE", QString::number(type + 1));
		PluginDebug(QString("Call URL for bunny '%1', ask by '%2'").arg(QString(b->GetID()), QString("OnClick, click %1").arg(QString(type + 1))));
		CallURL(b, url);
		return true;
	}
	return false;
}

void PluginCallURL::OnBunnyConnect(Bunny * b)
{
	QStringList old = b->GetPluginSetting(GetName(), "Urls", QStringList()).toStringList();
	if(old.count())
	{
		QMap<QString, QVariant> list;// = b->GetPluginSetting(GetName(), "Urls", QMap<QString, QVariant>()).toMap();
		PluginDebug("Found old settings, start converting", 2);
		int c = 1;
		foreach(QString url, old)
		{
			QString name = QString("Url%1").arg(c, 2, 10, QLatin1Char('0'));
			list.insert(name, url);
		}
		b->SetPluginSetting(GetName(), "Presets", list);
		PluginDebug("Url are now named", 2);
		b->RemovePluginSetting(GetName(), "Urls");

		QMap<QString, QVariant> schedules;
		list = b->GetPluginSetting(GetName(), "Webcasts", QMap<QString, QVariant>()).toMap();
		QMapIterator<QString, QVariant> i(list);
		while (i.hasNext()) {
			i.next();
			QString time = i.key();
			QString url = i.value().toString();
			int pos = old.indexOf(url);
			if(pos == -1)
			{
				LogError(QString("url %1 not found in list").arg(url));
			}
			else
			{
				QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
				schedules.insert("0|" + time, name);
			}
		}
		b->SetPluginSetting(GetName(), "Schedules", schedules);
		PluginDebug("Schedules now use named url", 2);
		b->RemovePluginSetting(GetName(), "Webcasts");

		QStringList commands = b->GetPluginSetting(GetName(), "Voice/List", QStringList()).toStringList();
		foreach(QString cmd, commands)
		{
			QString url = b->GetPluginSetting(GetName(), "Voice/" + cmd, "").toString();
			int pos = old.indexOf(url);
			if(pos == -1)
			{
				LogError(QString("url %1 not found in list").arg(url));
			}
			else
			{
				QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
				b->SetPluginSetting(GetName(), "Voice/" + cmd, name);
			}
		}
		PluginDebug("Voice commands now use named url", 2);
		QString defaultUrl = b->GetPluginSetting(GetName(), "DefaultVoice/CallURL", QString()).toString();
		if( defaultUrl.length())
		{
			int pos = old.indexOf(defaultUrl);
			if(pos == -1)
			{
				LogError(QString("url %1 not found in list").arg(defaultUrl));
			}
			else
			{
				QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
				b->SetPluginSetting(GetName(), "Default/Voice", name);
			}
		}
		PluginDebug("Default voice command now use named url", 2);

		for(int i=0; i<=16; i++)
		{
			QString leftUrl = b->GetPluginSetting(GetName(), QString("EarLeft/%1").arg(QString::number(i)), QString()).toString();
			if(leftUrl != QString())
			{
				int pos = old.indexOf(leftUrl);
				if(pos == -1)
				{
					LogError(QString("url %1 not found in list").arg(leftUrl));
				}
				else
				{
					QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
					b->SetPluginSetting(GetName(), QString("EarLeft/%1").arg(QString::number(i)), name);
				}
			}
			QString rightUrl = b->GetPluginSetting(GetName(), QString("EarRight/%1").arg(QString::number(i)), QString()).toString();
			if(rightUrl != QString())
			{
				int pos = old.indexOf(rightUrl);
				if(pos == -1)
				{
					LogError(QString("url %1 not found in list").arg(rightUrl));
				}
				else
				{
					QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
					b->SetPluginSetting(GetName(), QString("EarRight/%1").arg(QString::number(i)), name);
				}
			}
		}
		PluginDebug("Ears command now use named url", 2);
		defaultUrl = b->GetPluginSetting(GetName(), "DefaultLEar/CallURL", QString()).toString();
		if( defaultUrl.length())
		{
			int pos = old.indexOf(defaultUrl);
			if(pos == -1)
			{
				LogError(QString("url %1 not found in list").arg(defaultUrl));
			}
			else
			{
				QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
				b->SetPluginSetting(GetName(), QString("Default/RightEar"), name);
			}
		}
		PluginDebug("Default left ear command now use named url", 2);
		b->RemovePluginSetting(GetName(), "DefaultRLEar/CallURL");

		defaultUrl = b->GetPluginSetting(GetName(), "DefaultREar/CallURL", QString()).toString();
		if( defaultUrl.length())
		{
			int pos = old.indexOf(defaultUrl);
			if(pos == -1)
			{
				LogError(QString("url %1 not found in list").arg(defaultUrl));
			}
			else
			{
				QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
				b->SetPluginSetting(GetName(), QString("Default/LeftEar"), name);
			}
		}
		PluginDebug("Default right ear command now use named url", 2);
		b->RemovePluginSetting(GetName(), "DefaultREar/CallURL");

		QStringList settings = b->GetPluginSettings(GetName());
		QRegExp rx("RFIDCallURL/([a-fA-F0-9]+)$");
		list.clear();

		foreach(QString key, settings)
		{
			if(rx.indexIn(key) != -1)
			{
				QString url = b->GetPluginSetting(GetName(), key, QString()).toString();
				int pos = old.indexOf(url);
				if(pos == -1)
				{
					LogError(QString("url %1 not found in list").arg(url));
				}
				else
				{
					QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
					list.insert(QString(rx.cap(1)), name);
				}
				b->RemovePluginSetting(GetName(), key);
			}
		}
		b->SetPluginSetting(GetName(), "RFID", list);
		PluginDebug("RFID commands now use named url", 2);

		defaultUrl = b->GetPluginSetting(GetName(), "DefaultZtamp/CallURL", QString()).toString();
		if( defaultUrl.length())
		{
			int pos = old.indexOf(defaultUrl);
			if(pos == -1)
			{
				LogError(QString("url %1 not found in list").arg(defaultUrl));
			}
			else
			{
				QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
				b->SetPluginSetting(GetName(), QString("Default/RFID"), name);
			}
		}
		b->RemovePluginSetting(GetName(), "DefaultZtamp/CallURL");
		PluginDebug("Default RFID command now use named url", 2);

		defaultUrl = b->GetPluginSetting(GetName(), "Default/CallURL", "").toString();
		if( defaultUrl.length())
		{
			int pos = old.indexOf(defaultUrl);
			if(pos == -1)
			{
				LogError(QString("url %1 not found in list").arg(defaultUrl));
			}
			else
			{
				QString name = QString("Url%1").arg(pos + 1, 2, 10, QLatin1Char('0'));
				b->SetPluginSetting(GetName(), QString("Default/Click"), name);
			}
		}
		b->RemovePluginSetting(GetName(), "Default/CallURL");
		PluginDebug("Default url now use named url", 2);
	}

	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext())
	{
		i.next();
		QStringList when = i.key().split("|");
		if(when.count() != 2)
		{
			LogError(QString("Bad configuration for bunny '%1', plugin '%2'").arg(QString(b->GetID()), "callurl"));
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

QString PluginCallURL::MakeNextName(Bunny * b)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
	QRegExp rx("Url([0-9]+)$");
	QMapIterator<QString, QVariant> i(list);
	int max = 0;
	while (i.hasNext())
	{
		i.next();
		QString key = i.key();
		if(rx.indexIn(key) != -1)
		{
			int num = rx.cap(1).toInt();
			if(num > max)
				max = num;
		}
	}
	return QString("Url%1").arg(max + 1, 2, 10, QLatin1Char('0'));
}

void PluginCallURL::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginCallURL::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("url()", &PluginCallURL::Api_Url);
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginCallURL::Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", &PluginCallURL::Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("voice()", &PluginCallURL::Api_Voice);
	DECLARE_PLUGIN_BUNNY_API_CALL("ear()", &PluginCallURL::Api_Ear);
	DECLARE_PLUGIN_API_CALL("config()", &PluginCallURL::Api_Config);
}

PLUGIN_API_CALL(PluginCallURL::Api_Config)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "debug")
	{
		if(hRequest.HasArg("set"))
		{
			SetSettings("LogLevel", hRequest.GetArg("set"));
			logLevel = hRequest.GetArg("set").toInt();
			return new ApiAnswers::Ok(Translator::tr("Debug set to %1", account).arg(hRequest.GetArg("set")));
		}
		else
		{
			return new ApiAnswers::String(QString::number(GetSettings("LogLevel", 0).toInt()));
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginCallURL::Api_Url)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "rename")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));
		QString name = hRequest.GetArg("name");

		if(!hRequest.HasArg("new"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("new", GetName()));
		QString newname = hRequest.GetArg("new");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		if(list.contains(name))
		{
			QString url = list.value(name).toString();

			int removed = list.remove(name);
			if(removed > 0)
			{
				list.insert(newname, url);
				bunny->SetPluginSetting(GetName(), "Presets", list);
				return new ApiAnswers::Ok(Translator::tr("Url '%1' renamed to '%2' for bunny '%3'", account).arg(name, newname, QString(bunny->GetID())));
			}
			return new ApiAnswers::Error(Translator::tr("Url '%1' not found for bunny %2", account).arg(name, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Url '%1' not found for bunny %2", account).arg(name, QString(bunny->GetID())));
	}
	else if(action == "edit")
	{
		if(!hRequest.HasArg("url"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("url", GetName()));
		QString url = hRequest.GetArg("url");

		if(url.left(7) != "http://" && url.left(8) != "https://")
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("url", GetName()));

		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));
		QString name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		if(list.contains(name))
		{
			list.insert(name, url);
			bunny->SetPluginSetting(GetName(), "Presets", list);
			return new ApiAnswers::Ok(Translator::tr("Url '%1' modified for bunny '%2'", account).arg(url, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Url '%1' not found for bunny %2", account).arg(name, QString(bunny->GetID())));
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("url"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("url", GetName()));
		QString url = hRequest.GetArg("url");

		if(url.left(7) != "http://" && url.left(8) != "https://")
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("url", GetName()));

		QString name = MakeNextName(bunny);
		if(hRequest.HasArg("name"))
			name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		if(!list.contains(name))
		{
			list.insert(name, url);
			bunny->SetPluginSetting(GetName(), "Presets", list);
			return new ApiAnswers::Ok(Translator::tr("Added url '%1' for bunny '%2'", account).arg(url, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Url '%1' already exists for bunny '%2'", account).arg(name, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Presets", QMap<QString, QVariant>()).toMap();
		int removed = list.remove(name);
		if(removed > 0)
		{
			bunny->SetPluginSetting(GetName(), "Presets", list);
			return new ApiAnswers::Ok(Translator::tr("Removed url '%1' for bunny '%2'", account).arg(name, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Url '%1' not found for bunny %2", account).arg(name, QString(bunny->GetID())));
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		if(!hRequest.HasArg("type"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("type", GetName()));

		QString type = hRequest.GetArg("type");

		if(type != "LeftEar" && type != "RightEar" && type != "RFID" && type != "Voice" && type != "Click")
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin '%2'", account).arg("type", GetName()));

		bunny->SetPluginSetting(GetName(), "Default/" + type, name);
		return new ApiAnswers::Ok(QString("New '%3' url defined '%1' for bunny '%2'").arg(name, QString(bunny->GetID()), type));
	}
	else if(action == "get")
	{
		QMap<QString, QVariant> list;
		list.insert("Click", bunny->GetPluginSetting(GetName(), "Default/Click", QString()).toString());
		list.insert("LeftEar", bunny->GetPluginSetting(GetName(), "Default/LeftEar", QString()).toString());
		list.insert("RightEar", bunny->GetPluginSetting(GetName(), "Default/RightEar", QString()).toString());
		list.insert("RFID", bunny->GetPluginSetting(GetName(), "Default/RFID", QString()).toString());
		list.insert("Voice", bunny->GetPluginSetting(GetName(), "Default/Voice", QString()).toString());
		return new ApiAnswers::MappedList(list);
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginCallURL::Api_Ear)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QStringList ears;
		ears << "Left" << "Right";
		QMap<QString, QVariant> list;

		foreach(QString ear, ears)
		{
			for(int p=0; p<=16; p++)
			{
				list.insert(ear + QString::number(p), bunny->GetPluginSetting(GetName(), QString("Ear%1/%2").arg(ear, QString::number(p)), QString()).toString());
			}
		}
		return new ApiAnswers::MappedList(list);
	}
	else if(action == "add")
	{
		bunny->SetPluginSetting(GetName(), QString("Ear%1/%2").arg(hRequest.GetArg("ear"), hRequest.GetArg("pos")), hRequest.GetArg("url"));
		return new ApiAnswers::Ok(QString("Add url '%1' for '%2' ear at position '%3', bunny '%4'").arg(hRequest.GetArg("url"), hRequest.GetArg("ear"), hRequest.GetArg("pos"), QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		bunny->RemovePluginSetting(GetName(), QString("Ear%1/%2").arg(hRequest.GetArg("ear"), hRequest.GetArg("pos")));
		return new ApiAnswers::Ok(QString("Remove '%1' ear at position '%2' for bunny '%3'").arg(hRequest.GetArg("ear"), hRequest.GetArg("pos"), QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginCallURL::Api_Voice)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Voice", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("command"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("command", GetName()));

		QString command = hRequest.GetArg("command");

		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Voice", QMap<QString, QVariant>()).toMap();
		if(!list.contains(command))
		{
			list.insert(command, name);
			bunny->SetPluginSetting(GetName(), "Voice", list);
			return new ApiAnswers::Ok(Translator::tr("Add voice command '%1' for bunny '%2'").arg(command, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Voice command '%1' already assigned to bunny '%2'", account).arg(command, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("command"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("command", GetName()));

		QString command = hRequest.GetArg("command");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Voice", QMap<QString, QVariant>()).toMap();
		if(list.contains(command))
		{
			list.remove(command);
			bunny->SetPluginSetting(GetName(), "Voice", list);

			return new ApiAnswers::Ok(Translator::tr("Voice command '%1' removed for bunny '%2'", account).arg(command, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Voice command '%1' is not assign to bunny '%2'", account).arg(command, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginCallURL::Api_RFID)
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

		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, name);
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

PLUGIN_BUNNY_API_CALL(PluginCallURL::Api_Schedule)
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
