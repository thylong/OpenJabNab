#include <QMapIterator>
#include <QRandomGenerator>

#include "plugin_surprise.h"

#include "accountmanager.h"
#include "bunny.h"
#include "cron.h"
#include "packets/messagepacket.h"
#include "translator.h"
#include "tts/ttsmanager.h"

// +/- 20% - 30min => rand(24,36)
#define RANDOMIZEDRATIO 20

PluginSurprise::PluginSurprise()
	: PluginInterface("surprise", "Moods (Send random sounds at random intervals)",
										BunnyV1Plugin | BunnyV2Plugin | ApiPlugin | CronPlugin | RfidPlugin | VoicePlugin
									 )
{
}

const QHash<QString, QString> PluginSurprise::GetChangelog(void)
{
	QHash<QString, QString> revisions;
	revisions.insert("2.3.0", "Rename plugin to avoid mistakes");
	revisions.insert("2.3.1", "Insert a minimum time to avoid crash");
	revisions.insert("2.4.0", "Add support for Nabaztag V1");
	revisions.insert("2.4.1", "Fix bug for mp3 file listing");
	return revisions;
}

void PluginSurprise::createCron(Bunny * b, int frequency, QString surprise)
{
	if(!frequency)
	{
		LogDebug(QString("Bunny '%1' has invalid frequency '%2' for surprise '%3'").arg(b->GetID(), QString::number(frequency), surprise));
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();
		list.remove(surprise);
		b->SetPluginSetting(GetName(), "Surprises", list);
		return;
	}

	// Register cron
	int freq = GetRandomizedFrequency(frequency);
	//LogDebug(QString("Next %1 surprise for %2 in %3 min").arg(surprise, b->GetID(), QString::number(freq)));
	Cron::RegisterOneShot(this, freq, b, Cron::Random, surprise, NULL);
}

QString PluginSurprise::OnApiSpeak(Bunny *b, QVariant v)
{
	QString surprise = v.toString();
	PlaySurprise(b, surprise);
	return QString();
}

bool PluginSurprise::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		QString folder = list.value(rfid).toString();
		return PlaySurprise(b, folder);
	}
	return false;
}

bool PluginSurprise::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	float p = getPertinence(Translator::tr("mood,surprise", b), command);
	if (p > 0)
	{
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();
		QString folder = list.keys().at( QRandomGenerator::global()->generate() % list.count() );
		return PlaySurprise(b, folder);
	}
	return false;
}

void PluginSurprise::createCrons(Bunny * b)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

	// Check for previous configuration
	unsigned int frequency = b->GetPluginSetting(GetName(), "frequency", (uint)0).toUInt();
	QString surprise = b->GetPluginSetting(GetName(), "folder", QString()).toString();
	if(frequency && surprise.length() != 0)
	{
		LogInfo(QString(b->GetID()) + " already have an old config for surprise plugin");
		list.insert(surprise, frequency);
		b->RemovePluginSetting(GetName(), "frequency");
		b->RemovePluginSetting(GetName(), "folder");
		b->SetPluginSetting(GetName(), "Surprises", list);
	}

	// Register crons
	QMap<QString, QVariant>::iterator it;
	for (it = list.begin(); it != list.end(); ++it)
	{
		QString surprise = it.key().trimmed();
		if(surprise.length())
		{
			createCron(b, it.value().toInt(), it.key());
		}
		else
		{
		}
	}
}

int PluginSurprise::GetRandomizedFrequency(unsigned int freq)
{
	// 250 => ~30min, 125 => ~1h, 50 => ~2h30
	/*
	500	15	12	18
	250	30	24	36
	165	45	36	54
	125	60	48	72
	83	90	72	108
	62	121	97	145
	31	242	193	291
	*/
	unsigned int meanTimeInSec = (250.0/freq) * 30;

	int deviation = 0;

	if(RANDOMIZEDRATIO > 0 && RANDOMIZEDRATIO < 100)
	{
		unsigned int maxDeviation = (meanTimeInSec * 2 * RANDOMIZEDRATIO) / 100;
		if(maxDeviation > 0)
		{
			deviation = QRandomGenerator::global()->generate() % (maxDeviation);
		}
		deviation -= (maxDeviation/2);
	}
	int ret = meanTimeInSec + deviation;
	if(ret < 3)
	{
		LogDebug("New surprise in less than 3 minutes : " + QString::number(ret));
	}
	return qMax(3, ret);
}

void PluginSurprise::OnBunnyConnect(Bunny * b)
{
	createCrons(b);
}

void PluginSurprise::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginSurprise::OnCron(Bunny * b, QVariant data, unsigned int)
{
	QString surprise = data.toString();
	PlaySurprise(b, surprise);
	// Restart Timer
	QMap<QString, QVariant> surprises = b->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();
	createCron(b, surprises.value(surprise).toInt(), surprise);
}

bool PluginSurprise::PlaySurprise(Bunny * b, QString folder)
{
	if(b->IsIdle())
	{
		QByteArray file;
		// Fetch available files
		QDir * dir = GetLocalHTTPFolder();
		if(dir)
		{
			if(!folder.isNull())
			{
				QString accountName = b->GetGlobalSetting("OwnerAccount").toString();
				QStringList groups = AccountManager::ListSoundGroup(accountName);
				if(dir->cd(folder))
				{
					QStringList list = dir->entryList(QStringList("*.mp3"), QDir::Files|QDir::NoDotAndDotDot);
					if(list.count())
					{
						QString fileName = list.at(QRandomGenerator::global()->generate()%list.count());
						file = GetBroadcastHTTPPath(QString("%1/%3").arg(folder, fileName));
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
				else if(groups.contains(folder))
				{
					QStringList list = AccountManager::ListSoundsInGroup(accountName, folder);
					if(list.count())
					{
						QString fileName = list.at(QRandomGenerator::global()->generate()%list.count());
						file = b->GetBroadcastHTTPUserPath(fileName);
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
					LogError("Unknow folder or group");
					return false;
				}
			}
			else
			{
				LogError("Invalid surprise config");
				return false;
			}

			delete dir;
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

void PluginSurprise::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("setSurprise(name,frequency)", &PluginSurprise::Api_SetSurprise);
	DECLARE_PLUGIN_BUNNY_API_CALL("delSurprise(name)", &PluginSurprise::Api_DelSurprise);
	DECLARE_PLUGIN_BUNNY_API_CALL("getSurprises()", &PluginSurprise::Api_GetSurprises);
	DECLARE_PLUGIN_BUNNY_API_CALL("getFolderList()", &PluginSurprise::Api_GetFolderList);

	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginSurprise::Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("folder()", &PluginSurprise::Api_Folder);
	DECLARE_PLUGIN_BUNNY_API_CALL("surprise()", &PluginSurprise::Api_Surprise);
}

PLUGIN_BUNNY_API_CALL(PluginSurprise::Api_Surprise)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		if(!hRequest.HasArg("frequency"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("frequency", GetName()));

		QString folder = hRequest.GetArg("name");
		int frequency = hRequest.GetArg("frequency").toInt();
		if(frequency == 0)
		{
			QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

			list.remove(folder);
			bunny->SetPluginSetting(GetName(), "Surprises", list);
			OnBunnyDisconnect(bunny);
			OnBunnyConnect(bunny);
			return new ApiAnswers::Ok(Translator::tr("Removed surprise '%1'", account).arg(folder));
		}
		else
		{
			if(availableSurprises.contains(folder))
			{
				QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

				list.insert(folder, frequency);
				bunny->SetPluginSetting(GetName(), "Surprises", list);
				OnBunnyDisconnect(bunny);
				OnBunnyConnect(bunny);

				return new ApiAnswers::Ok(Translator::tr("Folder '%1' added", account).arg(folder));
			}
			QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
			QStringList groups = AccountManager::ListSoundGroup(accountName);
			if(groups.contains(folder))
			{
				QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

				list.insert(folder, frequency);
				bunny->SetPluginSetting(GetName(), "Surprises", list);
				OnBunnyDisconnect(bunny);
				OnBunnyConnect(bunny);

				return new ApiAnswers::Ok(Translator::tr("Sound group '%1' added", account).arg(folder));
			}
			return new ApiAnswers::Error(Translator::tr("Unknown folder or sound group : '%1'", account).arg(folder));
		}
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString folder = hRequest.GetArg("name");
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

		list.remove(folder);
		bunny->SetPluginSetting(GetName(), "Surprises", list);
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);
		return new ApiAnswers::Ok(Translator::tr("Removed surprise '%1'", account).arg(folder));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginSurprise::Api_Folder)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QDir * httpFolder = GetLocalHTTPFolder();
		if(httpFolder)
		{
			availableSurprises = httpFolder->entryList(QDir::Dirs|QDir::NoDotAndDotDot);
			delete httpFolder;
		}

		QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
		QStringList groups = AccountManager::ListSoundGroup(accountName);

		QStringList folders = availableSurprises << groups;
		folders.removeDuplicates();
		folders.sort();
		return new ApiAnswers::List(folders);
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginSurprise::Api_SetSurprise)
{
	Q_UNUSED(account);

	QString folder = hRequest.GetArg("name");
	int frequency = hRequest.GetArg("frequency").toInt();
	if(availableSurprises.contains(folder))
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

		list.insert(folder, frequency);
		// Save new config
		bunny->SetPluginSetting(GetName(), "Surprises", list);
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);

		return new ApiAnswers::Ok(QString("Folder '%1' added").arg(folder));
	}
	QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
	QStringList groups = AccountManager::ListSoundGroup(accountName);
	if(groups.contains(folder))
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

		list.insert(folder, frequency);
		// Save new config
		bunny->SetPluginSetting(GetName(), "Surprises", list);
		OnBunnyDisconnect(bunny);
		OnBunnyConnect(bunny);

		return new ApiAnswers::Ok(QString("Group '%1' added").arg(folder));
	}
	return new ApiAnswers::Error(QString("Unknown '%1' folder").arg(folder));
}

PLUGIN_BUNNY_API_CALL(PluginSurprise::Api_GetSurprises)
{
        Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap());
}

PLUGIN_BUNNY_API_CALL(PluginSurprise::Api_DelSurprise)
{
	Q_UNUSED(account);

	QString folder = hRequest.GetArg("name");
	QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Surprises", QMap<QString, QVariant>()).toMap();

	list.remove(folder);
	// Save new config
	bunny->SetPluginSetting(GetName(), "Surprises", list);
	OnBunnyDisconnect(bunny);
	OnBunnyConnect(bunny);
	return new ApiAnswers::Ok(QString("Plugin configuration updated."));
}

PLUGIN_BUNNY_API_CALL(PluginSurprise::Api_GetFolderList)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	// Check available folders and cache them
	QDir * httpFolder = GetLocalHTTPFolder();
	if(httpFolder)
	{
		availableSurprises = httpFolder->entryList(QDir::Dirs|QDir::NoDotAndDotDot);
		delete httpFolder;
	}

	//	QString accountName = QCryptographicHash::hash(bunny->GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
	QStringList groups = AccountManager::ListSoundGroup(accountName);

	QStringList folders = availableSurprises << groups;
	folders.removeDuplicates();
	folders.sort();
	return new ApiAnswers::List(folders);
}

PLUGIN_BUNNY_API_CALL(PluginSurprise::Api_RFID)
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

		if(!hRequest.HasArg("folder"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("folder", GetName()));

		QString folder = hRequest.GetArg("folder");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			if(availableSurprises.contains(folder))
			{
				list.insert(tag, folder);
				bunny->SetPluginSetting(GetName(), "RFID", list);
				Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
				z->Associate(bunny, this);
				return new ApiAnswers::Ok(Translator::tr("Add RFID '%1' for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
			}
			QString accountName = bunny->GetGlobalSetting("OwnerAccount").toString();
			QStringList groups = AccountManager::ListSoundGroup(accountName);
			if(groups.contains(folder))
			{
				list.insert(tag, folder);
				bunny->SetPluginSetting(GetName(), "RFID", list);
				Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
				z->Associate(bunny, this);
				return new ApiAnswers::Ok(Translator::tr("Add RFID '%1' for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
			}
			return new ApiAnswers::Error(Translator::tr("Unknown folder or sound group : '%1'", account).arg(folder));
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
