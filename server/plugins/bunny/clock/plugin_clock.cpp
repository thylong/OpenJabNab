#include <QDateTime>
#include <QMapIterator>
#include <QRandomGenerator>
#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "messagepacket.h"
#include "plugin_clock.h"
#include "ttsmanager.h"
#include "sentencemanager.h"
#include "translator.h"
#include "log.h"

PluginClock::PluginClock():PluginInterface("clock", "Clock", BunnyV1Plugin | BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | CronPlugin | VoicePlugin | ApiPlugin)
{
	Cron::Register(this, 30, 0, 0, NULL, Cron::Classic);
	// Check available folders
	QDir * httpFolder = GetLocalHTTPFolder();
	if(httpFolder)
	{
		availableVoices = httpFolder->entryList(QDir::Dirs|QDir::NoDotAndDotDot);
		delete httpFolder;
	}
	availableVoices.push_back("tts_fr");
	availableVoices.push_back("tts_us");
	availableVoices.push_back("tts_uk");
	availableVoices.push_back("tts_es");
	availableVoices.push_back("tts_ca");
	availableVoices.push_back("tts_de");
}

QString PluginClock::OnApiGet(Bunny *b, QVariant )
{
	sayTime(b);
	return QString();
}

PluginClock::~PluginClock()
{
	Cron::UnregisterAll(this);
}

/*
QStringList PluginClock::AdpFileToLoad(Bunny * b)
{
	//LogDebug("PluginClock::AdpFileToLoad");
	QDateTime now = QDateTime::currentDateTime();
	QDateTime lastDate = QDateTime::currentDateTime().addDays(-1);
	QString lastDateString = b->GetPluginSetting(GetName(), "lastDate", QString()).toString();
	if(lastDateString.length() > 0)
	{
		lastDate = QDateTime::fromString(lastDateString, "yyyy-MM-dd hh:mm:ss");
	}
	//LogDebug(lastDate.toString("yyyy-MM-dd hh:mm:ss"));
	if(lastDate < now && (lastDate.time().hour() != now.time().hour() || lastDate.date().day() != now.date().day() || lastDate.date().month() != now.date().month() || lastDate.date().year() != now.date().year()))
	//if(true)
	{
		QString voice = b->GetPluginSetting(GetName(), "voice", QString("tts_fr")).toString();
		QString hour = Translator::GetCurrentTime(b->GetGlobalSetting("TimeZone","UTC").toString()).toString("hh:mm");
		QByteArray file;
		if(voice == "tts_fr" || voice == "violet")
		{
			QString string = "Il est " + hour;
			TTSAnswer sound = TTSManager::CreateSound(string, "google/fr", "fr", TTSManager::Format_Adp);
			TTSLog(b->GetID(), GetName(), sound);
			b->SetPluginSetting(GetName(), "lastDate", now.toString("yyyy-MM-dd hh:mm:ss"));
			return QStringList() << sound.file;
		}
	}
	return QStringList();
}
*/

bool PluginClock::sayTime(Bunny * b)
{
	TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
	QString voice = b->GetPluginSetting(GetName(), "voice", QString("tts_fr")).toString();
	QString hour = Translator::GetCurrentTime(b->GetGlobalSetting("TimeZone","UTC").toString()).toString("hh:mm");
	QString file;
	if(voice == "tts_fr" || voice == "violet")
	{
		QString string = "Il est " + hour;
		TTSAnswer sound = TTSManager::CreateSoundWithGenre(string, b->GetVoice(), "fr", Voice::Woman, format, false);
		TTSLog(b->GetID(), GetName(), sound);
		file = sound.file;
	}
	else if( voice == "tts_ca")
	{
		QString string = "Il est " + hour;
		TTSAnswer sound = TTSManager::CreateSoundWithGenre(string, b->GetVoice(), "ca", Voice::Woman, format, false);
		TTSLog(b->GetID(), GetName(), sound);
		file = sound.file;
	}
	else if( voice == "tts_us")
	{
		QString string = "It's " + hour;
		TTSAnswer sound = TTSManager::CreateSoundWithGenre(string, b->GetVoice(), "us", Voice::Woman, format, false);
		TTSLog(b->GetID(), GetName(), sound);
		file = sound.file;
	}
	else if( voice == "tts_de")
	{
		QString string = "Es ist " + hour;
		TTSAnswer sound = TTSManager::CreateSoundWithGenre(string, b->GetVoice(), "de", Voice::Woman, format, false);
		TTSLog(b->GetID(), GetName(), sound);
		file = sound.file;
	}
	else if( voice == "tts_es")
	{
		QString string = "Es " + hour;
		TTSAnswer sound = TTSManager::CreateSoundWithGenre(string, b->GetVoice(), "es", Voice::Woman, format, false);
		TTSLog(b->GetID(), GetName(), sound);
		file = sound.file;
	}
	else
	{
		QString string = "It's " + hour;
		TTSAnswer sound = TTSManager::CreateSoundWithGenre(string, b->GetVoice(), "uk", Voice::Woman, format, false);
		TTSLog(b->GetID(), GetName(), sound);
		file = sound.file;
	}
	if(!file.isNull())
	{
		if(b->GetVersion() == 2)
		{
			QByteArray message = "MU " + file.toLatin1() + "\nMW\n";
			b->SendPacket(MessagePacket(message), GetName());
			return true;
		}
		else if(b->GetVersion() == 1)
		{
			AddSoundToSend(b, file);
		}
	}
	return false;
}

bool PluginClock::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("time,clock", b), command))
	{
		sayTime(b);
		return true;
	}
	return false;
}

bool PluginClock::OnClick(Bunny * b, PluginInterface::ClickType)
{
	return sayTime(b);
}

void PluginClock::OnCron(Bunny *, QVariant, unsigned int)
{
	QMapIterator<Bunny *, QString> i(bunnyList);
	while (i.hasNext())
	{
		i.next();
		Bunny * b = i.key();
		QString voice = i.value();
		int type = b->GetPluginSetting(GetName(), "type", Type_Voice).toInt();
		TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
		if(b->IsIdle() && type != Type_None)
		{
			QTime localTime = Translator::GetCurrentTime(b->GetGlobalSetting("TimeZone","UTC").toString()).time();
			if(localTime.minute() == 0 || (localTime.minute() == 30 && type == Type_SemiHourlyBell))
			{
				QString hour = localTime.toString("h");
				QString file;
				QString string;

				if(type == Type_Voice)
				{
					if(voice.startsWith("tts"))
					{
						QString language = voice;
						language.replace("tts_", "");
						string = SentenceManager::GetSentence("plugin_clock_hour_XXX", hour.toInt(), language);
						if(!string.length())
						{
							string = SentenceManager::GetSentence("plugin_clock_hour_XXX", hour.toInt(), b->GetLanguage());
							language = b->GetLanguage();
						}
						if(!string.length())
						{
							if(voice == "tts_fr")
							{
								string = "Il est " + hour + ":00";
								language = "fr";
							}
							else if( voice == "tts_ca")
							{
								string = "Il est " + hour + ":00";
								language = "ca";
							}
							else if( voice == "tts_us")
							{
								string = "It's " + hour + ":00";
								language = "us";
							}
							else if( voice == "tts_de")
							{
								string = "Es ist " + hour + ":00 Uhr";
								language = "de";
							}
							else if( voice == "tts_es")
							{
								string = "Es " + hour + ":00";
								language = "es";
							}
							else if(voice == "tts_uk")
							{
								string = "It's " + hour + ":00";
								language = "uk";
							}
						}
						if(string.length())
						{
							TTSAnswer sound = TTSManager::CreateSound(string, b->GetVoice(), language, format, false);
							TTSLog(b->GetID(), GetName(), sound);
							file = sound.file;
						}
					}
					else
					{
						// Fetch available files
						QDir * dir = GetLocalHTTPFolder();
						if(dir)
						{
							dir->cd(voice);
							dir->cd(hour);
							//QStringList filters;
							//filters << "*.mp3";
							QStringList list = dir->entryList(QStringList("*.mp3"), QDir::Files|QDir::NoDotAndDotDot);
							if(list.count())
							{
								QByteArray f = GetBroadcastHTTPPath(QString("%1/%2/%3").arg(voice, hour, list.at(QRandomGenerator::global()->generate()%list.count())));
								if(b->GetVersion() == 2)
								{
									file = QString(f);
								}
								else if(b->GetVersion() == 1)
								{
									file = TTSManager::convertToAdp(f, false, false, true);
								}
							}
							delete dir;
						}
						else
						{
							LogError("Invalid GetLocalHTTPFolder()");
						}
					}
				}
				else
				{
					// Bell
				}

				if(!file.isNull())
				{
					if(b->GetVersion() == 2)
					{
						QByteArray message = "MU " + file.toLatin1() + "\nMW\n";
						b->SendPacket(MessagePacket(message), GetName());
					}
					else if(b->GetVersion() == 1)
					{
						AddSoundToSend(b, file);
					}
				}
			}
		}

	}
}

void PluginClock::OnBunnyConnect(Bunny * b)
{
	QString voice = b->GetPluginSetting(GetName(), "voice", QString("tts")).toString();
	if(!availableVoices.contains(voice))
	{
		if(voice == "tts")
		{
			LogInfo(QString("Bunny '%1' switch from '%2' voice to fr").arg(b->GetID(), voice));
			voice = "tts_fr";
		}
		else if(voice == "tts_english")
		{
			LogInfo(QString("Bunny '%1' switch from '%2' voice to uk").arg(b->GetID(), voice));
			voice = "tts_uk";
		}
		else
		{
			LogError(QString("Bunny '%1' has invalid voice '%2'").arg(b->GetID(), voice));
			voice = "tts_fr";
		}
		b->SetPluginSetting(GetName(), "voice", voice);
	}
	bunnyList.insert(b, voice);
}

void PluginClock::OnBunnyDisconnect(Bunny * b)
{
	bunnyList.remove(b);
}

/*******
 * API *
 *******/

void PluginClock::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("setup()", &PluginClock::Api_Setup);
	DECLARE_PLUGIN_BUNNY_API_CALL("voice()", &PluginClock::Api_Voice);
	DECLARE_PLUGIN_BUNNY_API_CALL("setVoice(name)", &PluginClock::Api_SetVoice);
	DECLARE_PLUGIN_BUNNY_API_CALL("getVoiceList()", &PluginClock::Api_GetVoiceList);
}

PLUGIN_BUNNY_API_CALL(PluginClock::Api_Setup)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "hourly")
	{
		if(hRequest.HasArg("type"))
		{
			int type = hRequest.GetArg("type").toInt();
			if(type != Type_Voice && type != Type_HourlyBell && type != Type_SemiHourlyBell && type != Type_None)
			{
				type = Type_Voice;
			}
			bunny->SetPluginSetting(GetName(), "type", type);
			return new ApiAnswers::Ok(Translator::tr("Type changed to '%1'", account).arg(QString::number(type)));
		}
		return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "type", QString()).toString());
	}
	else
	{
		QString(bunny->GetID());
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginClock::Api_SetVoice)
{
	Q_UNUSED(account);

	QString voice = hRequest.GetArg("name");
	if(availableVoices.contains(voice))
	{
		// Update cache, set new voice
		bunnyList[bunny] = voice;
		// Save new config
		bunny->SetPluginSetting(GetName(), "voice", voice);

		return new ApiAnswers::Ok(Translator::tr("Voice changed to '%1'", account).arg(voice));
	}
	return new ApiAnswers::Error(Translator::tr("Unknown '%1' voice", account).arg(voice));
}

PLUGIN_BUNNY_API_CALL(PluginClock::Api_GetVoiceList)
{
	Q_UNUSED(account);
	Q_UNUSED(bunny);
	Q_UNUSED(hRequest);

	return new ApiAnswers::List(availableVoices);
}

PLUGIN_BUNNY_API_CALL(PluginClock::Api_Voice)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::List(availableVoices);
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString voice = hRequest.GetArg("name");
		if(availableVoices.contains(voice) || (voice == "RANDOM" && bunny->GetPluginSetting(GetName(), "voices", QStringList()).toStringList().length() > 0))
		{
			// Update cache, set new voice
			bunnyList[bunny] = voice;
			// Save new config
			bunny->SetPluginSetting(GetName(), "voice", voice);

			return new ApiAnswers::Ok(Translator::tr("Voice changed to '%1'", account).arg(voice));
		}
		return new ApiAnswers::Error(Translator::tr("Unknown '%1' voice", account).arg(voice));
	}
	else if(action == "get")
	{
		return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "voice", "").toString());
	}
	else if(action == "random")
	{
		if(!hRequest.HasArg("subaction"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("subaction", GetName()));

		QString subaction = hRequest.GetArg("subaction");

		if(subaction == "set")
		{
			if(!hRequest.HasArg("list"))
				return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("list", GetName()));

			QStringList list = hRequest.GetArg("list").split(",");
			QStringList voices;
			foreach(QString voice, list)
			{
				if(availableVoices.contains(voice))
				{
					voices << voice;
				}
			}
			if(voices.length())
			{
				bunny->SetPluginSetting(GetName(), "voices", voices);
				return new ApiAnswers::Ok(Translator::tr("List of random voices saved", account));
			}
			else
			{
				return new ApiAnswers::Error(Translator::tr("Unknown voices", account));
			}
		}
		else if(subaction == "get")
		{
			return new ApiAnswers::List(bunny->GetPluginSetting(GetName(), "voices", QStringList()).toStringList());
		}
		else
		{
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("subaction", GetName()));
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

