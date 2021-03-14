#include <QDateTime>
#include <QCryptographicHash>
#include <QXmlStreamReader>
#include <QMapIterator>
#include <QRegExp>
#include <QUrl>
#include <memory>
#include "bunny.h"
#include "accountmanager.h"
#include "bunnymanager.h"
#include "httprequest.h"
#include "log.h"
#include "cron.h"
#include "messagepacket.h"
#include "plugin_music.h"
#include "settings.h"
#include "ttsmanager.h"
#include "translator.h"

PluginMusic::PluginMusic():PluginInterface("music", "Music", BunnyV2Plugin | ZtampPlugin | SingleClickPlugin | DoubleClickPlugin  | CronPlugin | RfidPlugin | VoicePlugin) {}

bool PluginMusic::Init()
{
	QDir userFolder(GlobalSettings::GetString("Config/RealHttpRoot"));
	std::unique_ptr<QDir> dir(GetLocalHTTPFolder());
	if(dir.get())
	{
		QStringList filters;
		filters << "*.mp3";
		musicFolder = *dir;
		musicFolder.setNameFilters(filters);
		return true;
	}
	return false;
}

bool PluginMusic::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	float p = getPertinence(Translator::tr("play,mp3,music", b), command);
	if (p > 0)
	{
		QStringList musics = GetUserDir(b)->entryList(QStringList("*.mp3"));
		float maxPertinence = 0;
		QString maxFile = "";
		foreach(QString music, musics)
		{
			QString name = music.replace(".mp3", "").replace("-", " ").replace("_", " ");
			float pertinence = getPertinence(name, command);
			if(pertinence > maxPertinence)
			{
				maxPertinence = pertinence;
				maxFile = music;
			}
		}
		if(maxPertinence > 0)
		{
			return playFile(b, maxFile);
		}
		else
		{
			return playRandomFile(b);
		}
	}
	return false;
}

bool PluginMusic::OnRFID(Ztamp * z, Bunny * b)
{
	QString music = z->GetPluginSetting(GetName(), QString("Play"), QString()).toString();
	if(music != "")
	{
		if(music == "OJN_RANDOM")
		{
			return playRandomFile(b);
		}
		else
		{
			return playFile(b, music);
		}
	}
	return false;
}

bool PluginMusic::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		QString music = list.value(rfid).toString();
		if(music == "OJN_RANDOM")
		{
			return playRandomFile(b);
		}
		else if(music.startsWith("OJN_GROUP_"))
		{
			QString group = music.replace("OJN_GROUP_", "");
			return playRandomInGroup(b, group);
		}
		else
		{
			return playFile(b, music);
		}
	}
	return false;
}

bool PluginMusic::OnClick(Bunny * b, PluginInterface::ClickType)
{
	playRandomFile(b);
	return true;
}

bool PluginMusic::playRandomInGroup(Bunny * b, QString group)
{
	QString accountName = b->GetGlobalSetting("OwnerAccount").toString();
	QStringList musics = AccountManager::ListSoundsInGroup(accountName, group);

	int index = 0;
	if(musics.count() > 1)
	{
		index = QRandomGenerator::global()->generate() % musics.count();
		QString music = musics.at(index);
		return playFile(b, music);
	}
	return false;
}

bool PluginMusic::playRandomFile(Bunny * b)
{
	QStringList musics = GetUserDir(b)->entryList(QStringList("*.mp3"));
	int index = 0;
	if(musics.count() > 1)
	{
		index = QRandomGenerator::global()->generate() % musics.count();
		QString music = musics.at(index);
		return playFile(b, music);
	}
	return false;
}

bool PluginMusic::playFile(Bunny * b, QString f)
{
	if(f != "")
	{
		if(GetUserDir(b)->exists(f))
		{
			QByteArray message = "ST "+GetBroadcastHTTPUserPath(b, f)+"\nMW\n";
			b->SendPacket(MessagePacket(message), GetName());
			return true;
		}
		else if(musicFolder.exists(f))
		{
			QByteArray message = "ST "+GetBroadcastHTTPPath(f)+"\nMW\n";
			b->SendPacket(MessagePacket(message), GetName());
			return true;
		}
		return false;
	}
	return false;
}

QDir * PluginMusic::GetUserDir(Bunny * b)
{
	QString accountName = QCryptographicHash::hash(b->GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QDir userDir(GlobalSettings::GetString("Config/RealHttpRoot"));
	if (!userDir.cd("users"))
	{
		if (!userDir.mkdir("users"))
		{
			LogError(QString("Unable to create users directory !\n"));
		}
		userDir.cd("users");
	}
	if (!userDir.cd(accountName))
	{
		if (!userDir.mkdir(accountName))
		{
			LogError(QString("Unable to create " + accountName + " directory !\n"));
		}
		userDir.cd(accountName);
	}
	QStringList filters;
	filters << "*.mp3";
	userDir.setNameFilters(filters);
	return new QDir(userDir);
}

QByteArray PluginMusic::GetBroadcastHTTPUserPath(Bunny * b, QString f)
{
	QString userName = QCryptographicHash::hash(b->GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QString userFolder = QString("%1/%2/%3").arg(GlobalSettings::GetString("Config/HttpRoot"), GlobalSettings::GetString("Config/HttpUsersFolder"), userName);
	return QString("broadcast/%1/%2").arg(userFolder, f).toLatin1();
}
/*******
 * API *
 *******/

void PluginMusic::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginMusic::Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("file()", &PluginMusic::Api_File);
	DECLARE_PLUGIN_BUNNY_API_CALL("library()", &PluginMusic::Api_Library);
/*
	DECLARE_PLUGIN_BUNNY_API_CALL("addrfid(tag,music)", &PluginMusic::Api_AddRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("removerfid(tag)", &PluginMusic::Api_RemoveRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("listrfid()", &PluginMusic::Api_ListRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("getfileslist()", &PluginMusic::Api_getFilesList);
	DECLARE_PLUGIN_BUNNY_API_CALL("play(music)", &PluginMusic::Api_Play);
	DECLARE_PLUGIN_BUNNY_API_CALL("library()", &PluginMusic::Api_libraryMode);
*/
}

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_RFID)
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

		QString file;
		if(hRequest.HasArg("file"))
			file = hRequest.GetArg("file");

		if(hRequest.HasArg("random"))
			file = "OJN_RANDOM";

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, file);
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

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_File)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
		if(libraryMode & MixedLibrary)
			return new ApiAnswers::List(GetUserDir(bunny)->entryList(QStringList("*.mp3")) + musicFolder.entryList(QStringList("*.mp3")));
		return new ApiAnswers::List(GetUserDir(bunny)->entryList(QStringList("*.mp3")));
	}
	else if(action == "listgroup")
	{
		QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
		QStringList groups = AccountManager::ListSoundGroup(accountName);
		return new ApiAnswers::List(groups);
	}
	else if(action == "play")
	{
		if(!hRequest.HasArg("file"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("file", GetName()));

		QString file = hRequest.GetArg("file");
		playFile(bunny, file);
		return new ApiAnswers::Ok(Translator::tr("Will now play '%1' for bunny '%2'").arg(file, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_Library)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");


	if(action == "list")
	{
		int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
		QMap<QString, QVariant> list;

		list.insert("mixed", libraryMode & MixedLibrary);
		list.insert("own", libraryMode & OwnLibrary);
		list.insert("shared", libraryMode & SharedLibrary);
		list.insert("private", libraryMode & PrivateLibrary);

		return new ApiAnswers::MappedList(list);
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("mode"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("mode", GetName()));

		QString mode = hRequest.GetArg("mode");
		if(!hRequest.HasArg("share"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("share", GetName()));

		QString share = hRequest.GetArg("share");

		int libraryMode = NoLibrary;
		if(mode == "own")
			libraryMode |= OwnLibrary;
		else
			libraryMode |= MixedLibrary;

		if(share == "yes")
			libraryMode |= SharedLibrary;
		else
			libraryMode |= PrivateLibrary;

		bunny->SetPluginSetting(GetName(), QString("library"), (int)libraryMode);
		return new ApiAnswers::Ok(Translator::tr("Library setup updated", account));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
/*
PLUGIN_BUNNY_API_CALL(PluginMusic::Api_Play)
{
	Q_UNUSED(account);

	playFile(bunny, hRequest.GetArg("music"));

	return new ApiAnswers::Ok(QString("Will now play '%1' for bunny '%3'").arg(hRequest.GetArg("music"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_AddRFID)
{
	Q_UNUSED(account);

	bunny->SetPluginSetting(GetName(), QString("RFIDPlay/%1").arg(hRequest.GetArg("tag")), hRequest.GetArg("music"));

	return new ApiAnswers::Ok(QString("Add '%1' for RFID '%2', bunny '%3'").arg(hRequest.GetArg("music"), hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_RemoveRFID)
{
	Q_UNUSED(account);

	bunny->RemovePluginSetting(GetName(), QString("RFIDPlay/%1").arg(hRequest.GetArg("tag")));

	return new ApiAnswers::Ok(QString("Remove RFID '%2' for bunny '%3'").arg(hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_ListRFID)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	QMap<QString, QVariant> list;

	QStringList keys = bunny->GetPluginSettings(GetName());
	foreach(QString key, keys)
	{
		if(key.contains("RFIDPlay/"))
		{
			QStringList k = key.split("/");
			if(k.at(1).length() < 12)
			{
				list.insert(k.at(1).toLatin1().toHex(),  bunny->GetPluginSetting(GetName(), key, QString()));
				bunny->SetPluginSetting(GetName(), "RFIDPlay/" + k.at(1).toLatin1().toHex(), bunny->GetPluginSetting(GetName(), key, QString()));
				bunny->RemovePluginSetting(GetName(), "RFIDPlay/" + k.at(1));
				LogInfo("converting bad setting for bunny " + QString(bunny->GetID()));
			}
			else
			{
				list.insert(k.at(1),  bunny->GetPluginSetting(GetName(), key, QString()));
			}
		}
	}

	//list.insert("mixed", libraryMode & MixedLibrary);

	return new ApiAnswers::MappedList(list);
}

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_libraryMode)
{
	if(hRequest.HasArg("mode") && hRequest.HasArg("share"))
	{
		int libraryMode = NoLibrary;
		if(hRequest.GetArg("mode") == "own")
			libraryMode |= OwnLibrary;
		else
			libraryMode |= MixedLibrary;

		if(hRequest.GetArg("share") == "yes")
			libraryMode |= SharedLibrary;
		else
			libraryMode |= PrivateLibrary;
		bunny->SetPluginSetting(GetName(), QString("library"), (int)libraryMode);
		return new ApiAnswers::Ok(Translator::tr("Library setup updated", account));
	}
	else
	{
		int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
		QMap<QString, QVariant> list;

		list.insert("mixed", libraryMode & MixedLibrary);
		list.insert("own", libraryMode & OwnLibrary);
		list.insert("shared", libraryMode & SharedLibrary);
		list.insert("private", libraryMode & PrivateLibrary);

		return new ApiAnswers::MappedList(list);
	}
}

PLUGIN_BUNNY_API_CALL(PluginMusic::Api_getFilesList)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
	if(libraryMode & MixedLibrary)
		return new ApiAnswers::List(GetUserDir(bunny)->entryList() + musicFolder.entryList());
	return new ApiAnswers::List(GetUserDir(bunny)->entryList());
}
*/
