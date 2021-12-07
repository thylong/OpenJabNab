#include <QDateTime>
#include <QDebug>

#include "plugin_nab2nab.h"

#include "bunny.h"
#include "packets/messagepacket.h"
#include "tts/ttsmanager.h"
#include "translator.h"

PluginNab2nab::PluginNab2nab()
	: PluginInterface("nab2nab", "Send message to another bunny",
										BunnyV2Plugin | RfidPlugin | RecordPlugin | MessagePlugin
									 )
{
	announceText.append(Translator::tr("Never"));
	announceText.append(Translator::tr("On repeat"));
	announceText.append(Translator::tr("Always"));
}

void PluginNab2nab::OnBunnyConnect(Bunny * b)
{
	b->RemovePluginSetting(GetName(), "LastTag");
}

void PluginNab2nab::OnBunnyDisconnect(Bunny * b)
{
	b->RemovePluginSetting(GetName(), "LastTag");
}

QByteArray PluginNab2nab::GetRecordBroadcastHTTPPath(QString f) const
{
	QString recordFolder = QString("%1/%2/%3").arg(GlobalSettings::GetString("Config/HttpRoot"), GlobalSettings::GetString("Config/HttpPluginsFolder"), "record");
	return QString("broadcast/%1/%2").arg(recordFolder, f).toLatin1();
}

bool PluginNab2nab::OnRecord(Bunny * b, QString const& filename)
{
	QString sDate = b->GetPluginSetting(GetName(), "LastTagDate", QString()).toString();
	LogInfo(QString("Bunny %1 record a message").arg(QString(b->GetID())));
	if(sDate != QString())
	{
		QDateTime date = QDateTime::fromTime_t(sDate.toInt());
		QDateTime now = QDateTime::currentDateTime();
		if( date.addSecs(60) > now )
		{
			QString ztamp = b->GetPluginSetting(GetName(), "LastTag", QString()).toString();
			QMap<QString, QVariant> receivers = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
			if(receivers.contains(ztamp))
			{
				QString receiver = receivers.value(ztamp).toString();
				Bunny * bunny = BunnyManager::GetBunny(receiver.toLatin1());
				LogDebug("Receiver is " + bunny->GetBunnyName() + " (" + bunny->GetID() + ")");
				QString lng = b->GetLanguage();
				if(bunny->IsConnected())
				{
					QByteArray message = "ST "+GetRecordBroadcastHTTPPath(filename)+"\nMW\n";
					bunny->SendPacket(MessagePacket(message), GetName());
					SaveMessage(bunny, QString(GetRecordBroadcastHTTPPath(filename)));
					TTSAnswer fileName = TTSManager::CreateSound(Translator::tr("Message sent to %1", lng).arg(bunny->GetBunnyName()).toLatin1(), b->GetVoice(), lng);
					b->SendPacket(MessagePacket("MU " + fileName.file.toLatin1() + "\nMW\n"), GetName());
					LogInfo(QString("Bunny %1 is connected, sending message %2").arg(QString(bunny->GetID()), filename));
				}
				else
				{
					TTSAnswer fileName = TTSManager::CreateSound(Translator::tr("%1 is not available", lng).arg(bunny->GetBunnyName()).toLatin1(), b->GetVoice(), lng);
					b->SendPacket(MessagePacket("MU " + fileName.file.toLatin1() + "\nMW\n"), GetName());
					LogInfo(QString("Bunny %1 is not connected, can't send message %2").arg(QString(bunny->GetID()), filename));
				}

				b->RemovePluginSetting(GetName(), "LastTag");
				b->RemovePluginSetting(GetName(), "LastTagDate");
				return true;
			}
			else
			{
				LogInfo(QString("Bunny %1 snif a ztamp before, but it's not associated with a receiver").arg(QString(b->GetID())));
				b->RemovePluginSetting(GetName(), "LastTag");
				b->RemovePluginSetting(GetName(), "LastTagDate");
			}
			return false;
		}
		else
		{
			LogInfo(QString("Bunny %1 snif a ztamp before, but it's too late").arg(QString(b->GetID())));
			b->RemovePluginSetting(GetName(), "LastTag");
			b->RemovePluginSetting(GetName(), "LastTagDate");
		}
		return false;
	}
	else
	{
		LogInfo(QString("Bunny %1 didn't snif a ztamp before").arg(QString(b->GetID())));
	}
	return false;
}

bool PluginNab2nab::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> receivers = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	if(receivers.contains(tag.toHex()))
	{
		LogInfo(QString("Bunny %1 snif a ztamp to use nab2nab with %2").arg(QString(b->GetID()), QString(receivers.value(tag.toHex()).toString())));
		QString receiver = receivers.value(tag.toHex()).toString();
		Bunny * bunny = BunnyManager::GetBunny(receiver.toLatin1());
		QString lng = b->GetLanguage();
		if(bunny)
		{
			b->SetPluginSetting(GetName(), "LastTag", tag.toHex());
			b->SetPluginSetting(GetName(), "LastTagDate", QDateTime::currentDateTime().toTime_t());

			TTSAnswer fileName = TTSManager::CreateSound(Translator::tr("You have one minute to send your message to %1", lng).arg(bunny->GetBunnyName()).toLatin1(), b->GetVoice(), lng);
			b->SendPacket(MessagePacket("MU " + fileName.file.toLatin1() + "\nMW\n"), GetName());
			return true;
		}
		else
		{
			LogDebug("Receiver does not exist");
			TTSAnswer fileName = TTSManager::CreateSound(Translator::tr("Your setup for this plugin is bad : bunny does not exist", lng).toLatin1(), b->GetVoice(), lng);
			b->SendPacket(MessagePacket("MU " + fileName.file.toLatin1() + "\nMW\n"), GetName());
			return true;
		}
	}
	return false;
}

void PluginNab2nab::SendAudio(QString)
{
}

void PluginNab2nab::SendMessage(QString)
{
}

void PluginNab2nab::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("setpreference(key,value)", &PluginNab2nab::Api_SetPreference);
	DECLARE_PLUGIN_BUNNY_API_CALL("getpreference(key)", &PluginNab2nab::Api_GetPreference);
	DECLARE_PLUGIN_BUNNY_API_CALL("addfriend(sn,name)", &PluginNab2nab::Api_AddFavorite);
	DECLARE_PLUGIN_BUNNY_API_CALL("removefriend(sn)", &PluginNab2nab::Api_RemoveFavorite);
	DECLARE_PLUGIN_BUNNY_API_CALL("getfriends()", &PluginNab2nab::Api_GetFavorites);
	DECLARE_PLUGIN_BUNNY_API_CALL("setreceiverontag(sn,tag)", &PluginNab2nab::Api_SetReceiver);
	DECLARE_PLUGIN_BUNNY_API_CALL("removereceiverontag(tag)", &PluginNab2nab::Api_RemoveReceiver);
	DECLARE_PLUGIN_BUNNY_API_CALL("getreceiversontags()", &PluginNab2nab::Api_GetReceivers);

	DECLARE_PLUGIN_BUNNY_API_CALL("friend()", &PluginNab2nab::Api_Friend);
	DECLARE_PLUGIN_BUNNY_API_CALL("config()", &PluginNab2nab::Api_Config);
/*
	DECLARE_PLUGIN_API_CALL("sendmessage(sn,text)", &PluginNab2nab::Api_SendMessage);
	DECLARE_PLUGIN_API_CALL("sendaudio(sn,url)", &PluginNab2nab::Api_SendAudio);
	DECLARE_PLUGIN_API_CALL("receive(sn,url,keep)", &PluginNab2nab::Api_ReceiveMessage);
*/
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_Config)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "announce")
	{
		if(hRequest.HasArg("set"))
		{
			int set = hRequest.GetArg("set").toInt();
			if(set != None && set != Repeat && set != Always)
				return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("set", GetName()));

			bunny->SetPluginSetting(GetName(), "Config/Announce", set);

			return new ApiAnswers::Ok(Translator::tr("Bunny '%1' will announce message : %2", account).arg(QString(bunny->GetID()), announceText.at(set)));
		}
		else
		{
			return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "Config/Announce", QString()).toString());
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_Friend)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("sn"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sn", GetName()));

		QString sn = hRequest.GetArg("sn");

		QString name = sn;
		if(hRequest.HasArg("name"))
			name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap();
		if(!list.contains(sn))
		{
			list.insert(sn, name);
			bunny->SetPluginSetting(GetName(), "Friends", list);
			return new ApiAnswers::Ok(Translator::tr("Add bunny '%1' as friend named '%2'", account).arg(sn, name));
		}
		return new ApiAnswers::Error(Translator::tr("Bunny '%1' is already a friend", account).arg(sn));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("sn"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sn", GetName()));

		QString sn = hRequest.GetArg("sn");
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap();
		if(list.contains(sn))
		{
			list.remove(sn);
			bunny->SetPluginSetting(GetName(), "Friends", list);
			return new ApiAnswers::Ok(Translator::tr("Remove bunny '%1' from friends", account).arg(sn));
		}
		return new ApiAnswers::Error(Translator::tr("Bunny '%1' is not a friend", account).arg(sn));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
	Q_UNUSED(account);
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_AddFavorite)
{
	Q_UNUSED(account);

	QString b = hRequest.GetArg("sn");
	QString name = hRequest.GetArg("name");
	if(!bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap().contains(b))
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap();
		list.insert(b, name);
		bunny->SetPluginSetting(GetName(), "Friends", list);
		return new ApiAnswers::Ok(Translator::tr("Add bunny '%1' as friend named '%2'", account).arg(b, name));
	}
	return new ApiAnswers::Error(QString("Bunny '%1' is already a friend").arg(b));
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_RemoveFavorite)
{
	Q_UNUSED(account);

	QString b = hRequest.GetArg("sn");
	if(bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap().contains(b))
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap();
		list.remove(b);
		bunny->SetPluginSetting(GetName(), "Friends", list);
		return new ApiAnswers::Ok(QString("Remove bunny '%1' from friends").arg(b));
	}
	return new ApiAnswers::Error(QString("Bunny '%1' is not a friend").arg(b));
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_SetPreference)
{
	Q_UNUSED(account);

	bunny->SetPluginSetting(GetName(), hRequest.GetArg("key"), hRequest.GetArg("value"));
	return new ApiAnswers::Ok(QString("Preference updated"));
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_GetPreference)
{
	Q_UNUSED(account);
	return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), hRequest.GetArg("key"), QString()).toString());
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_GetFavorites)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Friends", QMap<QString, QVariant>()).toMap());
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_SetReceiver)
{
	if(!hRequest.HasArg("sn"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sn", GetName()));
	QString sn = hRequest.GetArg("sn");

	if(!hRequest.HasArg("tag"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));
	QString tag = hRequest.GetArg("tag");

	QMap<QString, QVariant> receivers = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	if(receivers.contains(tag))
	{
		return new ApiAnswers::Error(Translator::tr("Receiver is already defined for Ztamp '%1'", account).arg(tag));
	}
	else
	{
		if(sn != QString(""))
		{
			receivers.insert(tag, sn);
			bunny->SetPluginSetting(GetName(), "RFID", receivers);
			return new ApiAnswers::Ok(Translator::tr("Add bunny '%1' as friend for RFID '%2'", account).arg(sn, tag));
		}
		else
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("sn", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_RemoveReceiver)
{
	QString tag = hRequest.GetArg("tag");

	QMap<QString, QVariant> receivers = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	if(!receivers.contains(tag))
	{
		return new ApiAnswers::Error(Translator::tr("No receiver for Ztamp '%1'", account).arg(tag));
	}
	else
	{
		receivers.remove(tag);
		bunny->SetPluginSetting(GetName(), "RFID", receivers);
		return new ApiAnswers::Ok(Translator::tr("Remove receiver for Ztamp '%1'", account).arg(tag));
	}
}

PLUGIN_BUNNY_API_CALL(PluginNab2nab::Api_GetReceivers)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap());
}

/*
PLUGIN_API_CALL(PluginNab2nab::Api_SendMessage)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiAnswers::Ok("ok");
}

PLUGIN_API_CALL(PluginNab2nab::Api_SendAudio)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiAnswers::Ok("ok");
}

PLUGIN_API_CALL(PluginNab2nab::Api_ReceiveMessage)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	return new ApiAnswers::Ok("ok");
}
*/
