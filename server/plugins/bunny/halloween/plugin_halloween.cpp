#include <QMapIterator>
#include "plugin_halloween.h"
#include "accountmanager.h"
#include "bunny.h"
#include "cron.h"
#include "messagepacket.h"
#include "translator.h"
#include "ttsmanager.h"

// +/- 20% - 30min => rand(24,36)
#define RANDOMIZEDRATIO 20

PluginHalloween::PluginHalloween():PluginInterface("halloween", "Halloween sounds (Send random scary sounds at random intervals)",BunnyV1Plugin | BunnyV2Plugin | ApiPlugin | CronPlugin /*| RfidPlugin | VoicePlugin*/ ) {}

PluginHalloween::~PluginHalloween() {}

void PluginHalloween::createCron(Bunny * b, int min, int max)
{
	if(min == 0 || max == 0)
	{
		return;
	}
	// Register cron
	int delay = GetRandomizedDelay(min, max);
	//LogDebug(QString("Next %1 halloween for %2 in %3 min").arg(halloween, b->GetID(), QString::number(freq)));
	Cron::RegisterOneShot(this, delay, b, Cron::Random, QVariant(), NULL);
}

QString PluginHalloween::OnApiSpeak(Bunny *b, QVariant)
{
	PlaySound(b);
	return QString();
}

/*
bool PluginHalloween::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		QString folder = list.value(rfid).toString();
		return PlaySound(b, folder);
	}
	return false;
}

bool PluginHalloween::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	float p = getPertinence(Translator::tr("mood,halloween", b), command);
	if (p > 0)
	{
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap();
		QString folder = list.keys().at( qrand() % list.count() );
		return PlaySound(b, folder);
	}
	return false;
}
*/
int PluginHalloween::GetRandomizedDelay(unsigned int min, unsigned int max)
{
	int ret = min + (qrand() % ( max - min ));
	if(ret < 3)
	{
		LogDebug("New halloween in less than 3 minutes : " + QString::number(ret));
	}
	return qMax(3, ret);
}

void PluginHalloween::OnBunnyConnect(Bunny * b)
{
	int min = b->GetPluginSetting(GetName(), "Min", 0).toInt();
	int max = b->GetPluginSetting(GetName(), "Max", 0).toInt();
	createCron(b, min, max);
}

void PluginHalloween::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginHalloween::OnCron(Bunny * b, QVariant, unsigned int)
{
	PlaySound(b);
	// Restart Timer
	int min = b->GetPluginSetting(GetName(), "Min", 0).toInt();
	int max = b->GetPluginSetting(GetName(), "Max", 0).toInt();
	createCron(b, min, max);
}

bool PluginHalloween::PlaySound(Bunny * b)
{
	if(b->IsIdle())
	{
		QByteArray file;
		// Fetch available files
		QDir * dir = GetLocalHTTPFolder();
		if(dir)
		{
			QStringList list = dir->entryList(QStringList("*.mp3"), QDir::Files|QDir::NoDotAndDotDot);
			if(list.count())
			{
				QString fileName = list.at(qrand()%list.count());
				file = GetBroadcastHTTPPath(fileName);
				if(b->GetVersion() == 1)
				{
					fileName = TTSManager::convertToAdp(file, false, false, true);
					AddSoundToSend(b, fileName);
				}
				else
				{
					QByteArray message = "MU "+file+"\nMW\n";
					b->SendPacket(MessagePacket(message), GetName());
				}
				return true;
			}
		}
		else
		{
			LogError("Invalid GetLocalHTTPFolder()");
			return false;
		}
	}
	return false;
}

/*******
 * API *
 *******/

void PluginHalloween::InitApiCalls()
{
	//DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", PluginHalloween, Api_RFID);
	//DECLARE_PLUGIN_BUNNY_API_CALL("folder()", PluginHalloween, Api_Folder);
	DECLARE_PLUGIN_BUNNY_API_CALL("halloween()", PluginHalloween, Api_Sound);
}

PLUGIN_BUNNY_API_CALL(PluginHalloween::Api_Sound)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "set")
	{
		if(!hRequest.HasArg("min"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("min", GetName()));

		if(!hRequest.HasArg("max"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("max", GetName()));

		int min = hRequest.GetArg("min").toInt();
		int max = hRequest.GetArg("max").toInt();
		bunny->SetPluginSetting(GetName(), "Min", min);
		bunny->SetPluginSetting(GetName(), "Max", max);
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);

		return new ApiManager::ApiOk(Translator::tr("Halloween sounds added", account));
	}
	else if(action == "get")
	{
		return new ApiManager::ApiOk(QString("%1;%2").arg(bunny->GetPluginSetting(GetName(), "Min", 0).toInt(), bunny->GetPluginSetting(GetName(), "Max", 0).toInt()));
	}
	else if(action == "del")
	{
		bunny->RemovePluginSetting(GetName(), "Min");
		bunny->RemovePluginSetting(GetName(), "Max");
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);
		return new ApiManager::ApiOk(Translator::tr("Removed halloween sounds", account));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

/*
PLUGIN_BUNNY_API_CALL(PluginHalloween::Api_Folder)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QDir * httpFolder = GetLocalHTTPFolder();
		if(httpFolder)
		{
			availableSounds = httpFolder->entryList(QDir::Dirs|QDir::NoDotAndDotDot);
			delete httpFolder;
		}

		QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
		QStringList groups = AccountManager::ListSoundGroup(accountName); 

		QStringList folders = availableSounds << groups;
		folders.removeDuplicates();
		folders.sort(); 
		return new ApiManager::ApiList(folders);
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginHalloween::Api_SetSound)
{
	Q_UNUSED(account);

	QString folder = hRequest.GetArg("name");
	int frequency = hRequest.GetArg("frequency").toInt();
	if(availableSounds.contains(folder))
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap();

		list.insert(folder, frequency);
		// Save new config
		bunny->SetPluginSetting(GetName(), "Sounds", list);
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);

		return new ApiManager::ApiOk(QString("Folder '%1' added").arg(folder));
	}
	QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
	QStringList groups = AccountManager::ListSoundGroup(accountName); 
	if(groups.contains(folder))
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap();

		list.insert(folder, frequency);
		// Save new config
		bunny->SetPluginSetting(GetName(), "Sounds", list);
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);

		return new ApiManager::ApiOk(QString("Group '%1' added").arg(folder));
	}
	return new ApiManager::ApiError(QString("Unknown '%1' folder").arg(folder));
}

PLUGIN_BUNNY_API_CALL(PluginHalloween::Api_GetSounds)
{
        Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap());
}

PLUGIN_BUNNY_API_CALL(PluginHalloween::Api_DelSound)
{
	Q_UNUSED(account);

	QString folder = hRequest.GetArg("name");
	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap();

	list.remove(folder);
	// Save new config
	bunny->SetPluginSetting(GetName(), "Sounds", list);
	OnBunnyDisconnect(bunny);
	OnBunnyConnect(bunny);
	return new ApiManager::ApiOk(QString("Plugin configuration updated."));
}

PLUGIN_BUNNY_API_CALL(PluginHalloween::Api_GetFolderList)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	// Check available folders and cache them
	QDir * httpFolder = GetLocalHTTPFolder();
	if(httpFolder)
	{
		availableSounds = httpFolder->entryList(QDir::Dirs|QDir::NoDotAndDotDot);
		delete httpFolder;
	}

	//	QString accountName = QCryptographicHash::hash(bunny->GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
	QStringList groups = AccountManager::ListSoundGroup(accountName); 

	QStringList folders = availableSounds << groups;
	folders.removeDuplicates();
	folders.sort(); 
	return new ApiManager::ApiList(folders);
}

PLUGIN_BUNNY_API_CALL(PluginHalloween::Api_RFID)
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

		if(!hRequest.HasArg("folder"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("folder", GetName()));

		QString folder = hRequest.GetArg("folder");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			if(availableSounds.contains(folder))
			{
				list.insert(tag, folder);
				bunny->SetPluginSetting(GetName(), "RFID", list);
				Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
				z->Associate(bunny, this);
				return new ApiManager::ApiOk(Translator::tr("Add RFID '%1' for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
			}
			QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
			QStringList groups = AccountManager::ListSoundGroup(accountName); 
			if(groups.contains(folder))
			{
				list.insert(tag, folder);
				bunny->SetPluginSetting(GetName(), "RFID", list);
				Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
				z->Associate(bunny, this);
				return new ApiManager::ApiOk(Translator::tr("Add RFID '%1' for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
			}
			return new ApiManager::ApiError(Translator::tr("Unknown folder or sound group : '%1'", account).arg(folder));
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
*/

