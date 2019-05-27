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
#include "plugin_nabcast.h"
#include "settings.h"
#include "ttsmanager.h"
#include "translator.h"

PluginNabcast::PluginNabcast():PluginInterface("nabcast", "Nabcazts", BunnyV2Plugin | ZtampPlugin | SingleClickPlugin | DoubleClickPlugin  | CronPlugin | RfidPlugin | VoicePlugin)
{
}

bool PluginNabcast::Init()
{
	QDir userFolder(GlobalSettings::GetString("Config/RealHttpRoot"));
	std::unique_ptr<QDir> dir(GetLocalHTTPFolder());
	if(dir.get())
	{
		QStringList filters;
		filters << "*.mp3";
		nabcastFolder = *dir;
		nabcastFolder.setNameFilters(filters);
		return true;
	}
	return false;
}

bool PluginNabcast::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	float p = getPertinence(Translator::tr("play,mp3,nabcast", b), command);
	if (p > 0)
	{
		QStringList nabcasts = GetUserDir(b)->entryList(QStringList("*.mp3"));
		float maxPertinence = 0;
		QString maxFile = "";
		foreach(QString nabcast, nabcasts)
		{
			QString name = nabcast.replace(".mp3", "").replace("-", " ").replace("_", " ");
			float pertinence = getPertinence(name, command);
			if(pertinence > maxPertinence)
			{
				maxPertinence = pertinence;
				maxFile = nabcast;
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

bool PluginNabcast::OnRFID(Ztamp * z, Bunny * b)
{
	QString nabcast = z->GetPluginSetting(GetName(), QString("Play"), QString()).toString();
	if(nabcast != "")
	{
		if(nabcast == "OJN_RANDOM")
		{
			return playRandomFile(b);
		}
		else
		{
			return playFile(b, nabcast);
		}
	}
	return false;
}

bool PluginNabcast::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		QString nabcast = list.value(rfid).toString();
		if(nabcast == "OJN_RANDOM")
		{
			return playRandomFile(b);
		}
		else
		{
			return playFile(b, nabcast);
		}
	}
	return false;
}

bool PluginNabcast::OnClick(Bunny * b, PluginInterface::ClickType)
{
	playRandomFile(b);
	return true;
}

bool PluginNabcast::playRandomFile(Bunny * b)
{
	QStringList nabcasts = GetUserDir(b)->entryList(QStringList("*.mp3"));
	int index = 0;
	if(nabcasts.count() > 1)
	{
		index = qrand() % nabcasts.count();
		QString nabcast = nabcasts.at(index);
		return playFile(b, nabcast);
	}
	return false;
}

bool PluginNabcast::playFile(Bunny * b, QString f)
{
	if(f != "")
	{
		if(GetUserDir(b)->exists(f))
		{
			QByteArray message = "ST "+GetBroadcastHTTPUserPath(b, f)+"\nMW\n";
			b->SendPacket(MessagePacket(message), GetName());
			return true;
		}
		else if(nabcastFolder.exists(f))
		{
			QByteArray message = "ST "+GetBroadcastHTTPPath(f)+"\nMW\n";
			b->SendPacket(MessagePacket(message), GetName());
			return true;
		}
		return false;
	}
	return false;
}

QDir * PluginNabcast::GetUserDir(Bunny * b)
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

QByteArray PluginNabcast::GetBroadcastHTTPUserPath(Bunny * b, QString f)
{
	QString userName = QCryptographicHash::hash(b->GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QString userFolder = QString("%1/%2/%3").arg(GlobalSettings::GetString("Config/HttpRoot"), GlobalSettings::GetString("Config/HttpUsersFolder"), userName);
	return QString("broadcast/%1/%2").arg(userFolder, f).toLatin1();
}
/*******
 * API *
 *******/

void PluginNabcast::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", PluginNabcast, Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("file()", PluginNabcast, Api_File);
	DECLARE_PLUGIN_BUNNY_API_CALL("library()", PluginNabcast, Api_Library);
/*
	DECLARE_PLUGIN_BUNNY_API_CALL("addrfid(tag,nabcast)", PluginNabcast, Api_AddRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("removerfid(tag)", PluginNabcast, Api_RemoveRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("listrfid()", PluginNabcast, Api_ListRFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("getfileslist()", PluginNabcast, Api_getFilesList);
	DECLARE_PLUGIN_BUNNY_API_CALL("play(nabcast)", PluginNabcast, Api_Play);
	DECLARE_PLUGIN_BUNNY_API_CALL("library()", PluginNabcast, Api_libraryMode);
*/
}

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_RFID)
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
			return new ApiManager::ApiOk(Translator::tr("Add RFID '%1' for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
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

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_File)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
		if(libraryMode & MixedLibrary)
			return new ApiManager::ApiList(GetUserDir(bunny)->entryList(QStringList("*.mp3")) + nabcastFolder.entryList(QStringList("*.mp3")));
		return new ApiManager::ApiList(GetUserDir(bunny)->entryList(QStringList("*.mp3")));
	}
	else if(action == "play")
	{
		if(!hRequest.HasArg("file"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("file", GetName()));

		QString file = hRequest.GetArg("file");
		playFile(bunny, file);
		return new ApiManager::ApiOk(Translator::tr("Will now play '%1' for bunny '%2'").arg(file, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_Library)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");


	if(action == "list")
	{
		int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
		QMap<QString, QVariant> list;

		list.insert("mixed", libraryMode & MixedLibrary);
		list.insert("own", libraryMode & OwnLibrary);
		list.insert("shared", libraryMode & SharedLibrary);
		list.insert("private", libraryMode & PrivateLibrary);

		return new ApiManager::ApiMappedList(list);
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("mode"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("mode", GetName()));

		QString mode = hRequest.GetArg("mode");
		if(!hRequest.HasArg("share"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("share", GetName()));

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
		return new ApiManager::ApiOk(Translator::tr("Library setup updated", account));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
/*
PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_Play)
{
	Q_UNUSED(account);

	playFile(bunny, hRequest.GetArg("nabcast"));

	return new ApiManager::ApiOk(QString("Will now play '%1' for bunny '%3'").arg(hRequest.GetArg("nabcast"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_AddRFID)
{
	Q_UNUSED(account);

	bunny->SetPluginSetting(GetName(), QString("RFIDPlay/%1").arg(hRequest.GetArg("tag")), hRequest.GetArg("nabcast"));

	return new ApiManager::ApiOk(QString("Add '%1' for RFID '%2', bunny '%3'").arg(hRequest.GetArg("nabcast"), hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_RemoveRFID)
{
	Q_UNUSED(account);

	bunny->RemovePluginSetting(GetName(), QString("RFIDPlay/%1").arg(hRequest.GetArg("tag")));

	return new ApiManager::ApiOk(QString("Remove RFID '%2' for bunny '%3'").arg(hRequest.GetArg("tag"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_ListRFID)
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

	return new ApiManager::ApiMappedList(list);
}

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_libraryMode)
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
		return new ApiManager::ApiOk(Translator::tr("Library setup updated", account));
	}
	else
	{
		int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
		QMap<QString, QVariant> list;

		list.insert("mixed", libraryMode & MixedLibrary);
		list.insert("own", libraryMode & OwnLibrary);
		list.insert("shared", libraryMode & SharedLibrary);
		list.insert("private", libraryMode & PrivateLibrary);

		return new ApiManager::ApiMappedList(list);
	}
}

PLUGIN_BUNNY_API_CALL(PluginNabcast::Api_getFilesList)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	
	int libraryMode = bunny->GetPluginSetting(GetName(), QString("library"), (int)(MixedLibrary | PrivateLibrary)).toInt();
	if(libraryMode & MixedLibrary)
		return new ApiManager::ApiList(GetUserDir(bunny)->entryList() + nabcastFolder.entryList());
	return new ApiManager::ApiList(GetUserDir(bunny)->entryList());
}
*/
