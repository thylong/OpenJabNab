#include "plugin_voicecommand.h"
#include <memory>
#include <QRegExp>
#include <QProcess>
#include <QNetworkAccessManager>
#include <QNetworkRequest>
#include <QNetworkReply>
#include <QUrl>

#include <QFileInfo>
#include <QFile>
#include "account.h"
#include "accountmanager.h"
#include "bunny.h"
#include "dbmanager.h"
#include "translator.h"
#include "ttsmanager.h"
#include "messagepacket.h"
//#include "voicelog.h"
#include "QsLog.h"

PluginVoiceCommand::PluginVoiceCommand()
  : PluginInterface("voicecommand", "Voice recognition", SystemPlugin | SystemAfterPlugin)
  , _http(this)
{
  QObject::connect(&_http, &QNetworkAccessManager::finished, this, &PluginVoiceCommand::recognitionFinished);
}

bool PluginVoiceCommand::OnRecord(Bunny * b, QString const& filename)
{
	Account * a = AccountManager::GetAccountByLogin(b->GetGlobalSetting("OwnerAccount").toByteArray());
	if(a != NULL)
	{
		if( a->IsVip() || a->IsAdmin() || a->IsPremium())
		{
			if( a->GetAbuse() )
			{
				QString lng = makeLanguage(b->GetLanguage());
				LogInfo("Voice command asked but user is banned");
				TTSAnswer sound = TTSManager::CreateSoundWithGenre(Translator::tr("I don't understand your question", lng), b->GetVoice(), lng, Voice::Woman);
				QByteArray message = "MU " + sound.file.toLatin1() + "\nMW\n";
				TTSLog(b->GetID(), GetName(), sound);
				b->SendPacket(MessagePacket(message), GetName());
				return true;
			}
			else
			{
				QString program = "/usr/bin/sox";
				QStringList arguments;
				QFileInfo infos(filename);
				QString command = GetLocalHTTPFolder()->absolutePath() + "/" + infos.baseName() + ".flac";
				QString wav = GetLocalHTTPFolder()->absolutePath();
				wav = wav.replace("voicecommand", "record") + "/" + filename;
				arguments << wav << command << "rate" << "16k";

    				QsLogging::Logger::DebugLog(QString("%1 %2").arg(program, arguments.join(" ")), "VoicePlugin");

				char buffer[1024];
				FILE* fd = popen(QString("%1 %2").arg(program, arguments.join(" ")).toLatin1(), "r");
				if (fd != NULL) {

					while(NULL != fgets(buffer, sizeof(buffer), fd)) {
						QString s(buffer);
						//s = s.stripWhiteSpace();
					        //do something with s
					}
					pclose (fd);
					//delete fd;
					//QProcess *convertProcess = new QProcess(this);
					//convertProcess->execute(program, arguments);
	/*
					if(convertProcess)
					{
						convertProcess->waitForFinished (-1);
	*/
						QFile file(command);
						if (file.open (QIODevice::ReadWrite) || file.open (QIODevice::ReadOnly))
						{
							QByteArray flac = file.readAll();
							QString lng = makeLanguage(b->GetLanguage());
							file.close();
              if(flac.size() == 0)
                return false;

							QString key = "AIzaSyAWY47hzyclccmabVobulOyH48U4Xo4LnE";
							QStringList keys = b->GetPluginSetting(GetName(), "GoogleKeys", QStringList()).toStringList();
							if(keys.count() > 0)
							{
								int index = b->GetPluginSetting(GetName(), "GoogleKeyIndex", -1).toInt() + 1;
								if(index > keys.count() - 1)
								{
									index = 0;
								}
								key = keys.at(index);
								b->SetPluginSetting(GetName(), "GoogleKeyIndex", index);
								LogInfo("Using bunny key " + key + " for bunny " + QString(b->GetID()));
							}
							else
							{
								keys = GetSettings("GoogleKeys", QStringList()).toStringList();
								if(keys.count() > 0)
								{
									int index = GetSettings("GoogleKeyIndex", -1).toInt() + 1;
									if(index > keys.count() - 1)
									{
										index = 0;
									}
									key = keys.at(index);
									SetSettings("GoogleKeyIndex", index);
									LogInfo("Using server key " + key + " for bunny " + QString(b->GetID()));
								}
								else
								{
									LogInfo("Using default key " + key + " for bunny " + QString(b->GetID()));
								}
							}

							QUrl url = "http://www.google.com/speech-api/v2/recognize?lang=" + lng + "&key=" + key + "&output=json";
							QsLogging::Logger::DebugLog(QString("POST %1").arg(url.toString()), GetName());

              QNetworkRequest req(url);
							req.setAttribute(QNetworkRequest::User, b->GetID());
							req.setRawHeader("Host", "www.google.com");
							req.setRawHeader("Content-Type", "audio/x-flac; rate=16000");
							req.setRawHeader("Keep-Alive", "300");
							req.setRawHeader("Connection", "keep-alive");
							//req.setRawHeader("User-Agent", "speech2text");
							_http.post(req, flac);
							return true;
						}
						else
						{
							LogError("Error loading FLAC file");
						}

					}
/*
				}
				else
				{
					LogError("No process found");
					QString lng = makeLanguage(b->GetLanguage());
					TTSAnswer sound = TTSManager::CreateSoundWithGenre(Translator::tr("I don't understand your question", lng), b->GetVoice(), lng, Voice::Woman);
					QByteArray message = "MU " + sound.file.toLatin1() + "\nMW\n";
					TTSLog(b->GetID(), GetName(), sound);
					b->SendPacket(MessagePacket(message), GetName());
					return true;
				}
*/
					//delete fd;
			}
		}
		else
		{
			LogDebug(QString("Bunny %1 is not authorized (%2)").arg(QString(b->GetID()), "Bad status"));
		}
	}
	else
	{
		LogDebug(QString("Bunny %1 is not authorized (%2)").arg(QString(b->GetID()), "No account"));
	}
	return false;
}

void PluginVoiceCommand::analyzeWords(Bunny * bunny, QString string, bool abuse)
{
	int count = 0;
	QStringList words = string.split(" ");
	QStringList forbiddens = GetSettings(bunny->GetLanguage() + "/Forbidden", QStringList()).toStringList();
	foreach(QString word, words)
	{
		if(forbiddens.contains(word)) {
			count++;
		}
	}
	if(!abuse)
	{
		if(count >= 3)
		{
			count = floor((float)count / 2);
		}
		else
		{
			count = 0;
		}
	}
	if(count >= 1)
	{
		Account * a = AccountManager::GetAccountByLogin(bunny->GetGlobalSetting("OwnerAccount").toByteArray());
		for(int i=0; i<count; i++)
		{
			a->SetAbuse();
		}
	}
}

bool PluginVoiceCommand::saveWords(Bunny * bunny, QString string)
{
	bool abuse = false;
	QStringList words = string.split(" ");
	QSqlDatabase db = DbManager::getDb();
	bool close = DbManager::openDbIfNeeded();
	QStringList links = GetSettings(bunny->GetLanguage() + "/Link", QStringList()).toStringList();
	QStringList forbiddens = GetSettings(bunny->GetLanguage() + "/Forbidden", QStringList()).toStringList();
	foreach(QString word, words)
	{
		if(!links.contains(word)) {
			QSqlQuery *query = new QSqlQuery(db);
			query->prepare("INSERT INTO voice_words SET `mac`=:mac, `word`=:word");
			query->bindValue(":mac", bunny->GetID());
			query->bindValue(":word", word.trimmed());
			query->exec();
			delete query;
		}
		if(forbiddens.contains(word)) {
			Account * a = AccountManager::GetAccountByLogin(bunny->GetGlobalSetting("OwnerAccount").toByteArray());
			a->SetAbuse();
			abuse = true;
		}
	}
	if(close)
		DbManager::releaseDb();
	return abuse;
}

QString PluginVoiceCommand::cleanString(QString str)
{
	return str.toLower().replace(QRegExp(QString::fromUtf8("[éèëê]")), "e").replace(QRegExp(QString::fromUtf8("[âäáàãå]")), "a").replace(QRegExp(QString::fromUtf8("[ç]")), "c").replace(QRegExp(QString::fromUtf8("[îïíì]")), "i").replace(QRegExp(QString::fromUtf8("[ñ]")), "n").replace(QRegExp(QString::fromUtf8("[ôöóòõø]")), "o").replace(QRegExp(QString::fromUtf8("[ß]")), "ss").replace(QRegExp(QString::fromUtf8("[ûüúù]")), "u").replace(QRegExp(QString::fromUtf8("[æ]")), "ae").replace(QRegExp(QString::fromUtf8("[œ]")), "oe").trimmed();
}

void PluginVoiceCommand::recognitionFinished(QNetworkReply* rep)
{
  LogDebug("recognitionFinished");
  QString result = rep->readAll();
  LogDebug(result);

  QString content = cleanString(QString::fromUtf8(result.toLatin1()));
  Bunny * b = BunnyManager::GetBunny(this, rep->request().attribute(QNetworkRequest::User,QByteArray()).toByteArray());
  delete rep;
  if(!b)
    return;
  QString lng = b->GetLanguage();

  // {"status":0,"id":"471e9f18b1f0faa58440cf6bc77922c2-1","hypotheses":[{"utterance":"ceci est un test","confidence":0.7537645},{"utterance":"ceci est un texte"},{"utterance":"ceci est un text"},{"utterance":"ceci est le test"},{"utterance":"ceci est bête"},{"utterance":"ceci est un texto"},{"utterance":"ceci étant un text"}]}
  LogDebug("Result from speech2text : " + content);

  //QRegExp rx("\\{\"utterance\":\"(.*)\",\"confidence\":(\\d+\\.\\d+)\\}");
  QRegExp rx("\\{\"transcript\":\"(.*)\",\"confidence\":(\\d+\\.\\d+)\\}");
  rx.setMinimal(true);
  int pos = 0;
  if((pos = rx.indexIn(content)) != -1 ) {
    pos += rx.matchedLength();
    QString command = rx.cap(1).toLower();
    //VoiceLog::Log(QString(b->GetID()) + "/" + b->GetLanguage(), "Found command : " + command);
    QsLogging::Logger::VoiceLog("Found command : " + command, b->GetID(), b->GetLanguage());
    bool abuse = saveWords(b, command);

    QStringList otherCmds;
    //rx.setPattern("\\{\"utterance\":\"(.*)\"\\}");
    rx.setPattern("\\{\"transcript\":\"(.*)\"\\}");
    while((pos = rx.indexIn(content, pos)) != -1 )
    {
      pos += rx.matchedLength();
      QString cmd = rx.cap(1).toLower();
      otherCmds << cmd;
    }
    otherCmds.removeDuplicates();
    foreach(QString cmd, otherCmds)
    {
      //VoiceLog::Log(QString(b->GetID()) + "/" + b->GetLanguage(), "Found other command : " + cmd);
      QsLogging::Logger::VoiceLog("Found other command : " + cmd, b->GetID(), b->GetLanguage());
      analyzeWords(b, cmd, abuse);
    }
    if ( ! b->OnVoiceCommand(command, otherCmds))
    {
      TTSAnswer sound = TTSManager::CreateSoundWithGenre(Translator::tr("You ask for", lng), b->GetVoice(), lng, Voice::Woman);
      QByteArray message = "MU " + sound.file.toLatin1() + "\nMW\n";
      TTSLog(b->GetID(), GetName(), sound);
      TTSAnswer soundBis = TTSManager::CreateSoundWithGenre(command, b->GetVoice(), lng, Voice::Woman);
      message += "MU " + soundBis.file.toLatin1() + "\nMW\n";
      TTSLog(b->GetID(), GetName(), soundBis);
      b->SendPacket(MessagePacket(message), GetName());
    }
  }
  else
  {
    TTSAnswer sound = TTSManager::CreateSoundWithGenre(Translator::tr("I don't understand your question", lng), b->GetVoice(), lng, Voice::Woman);
    QByteArray message = "MU " + sound.file.toLatin1() + "\nMW\n";
    TTSLog(b->GetID(), GetName(), sound);
    LogDebug("No command found");
    QsLogging::Logger::VoiceLog("No command found (" + content + ")", b->GetID(), b->GetLanguage());
    b->SendPacket(MessagePacket(message), GetName());
  }
}

bool PluginVoiceCommand::OnVoiceBeforeBunny(QString const& command, QString const& otherCmds)
{
	if(command == otherCmds)
	{
		return false;
	}
	return true;
}

bool PluginVoiceCommand::OnVoiceAfterBunny(QString const& command, QString const& otherCmds)
{
	if(command == otherCmds)
	{
		return false;
	}
	return true;
}

QString PluginVoiceCommand::makeLanguage(QString lng)
{
	QString l = GetSettings("Languages/" + lng, QString()).toString();
	if(l == QString())
	{
		QString approx = GlobalSettings::Get("TTSApprox/" + lng, lng).toString();
		if(approx != lng)
		{
			l = GetSettings("Languages/" + approx, QString()).toString();
		}
	}
	if(l == QString())
		l = GetSettings("Languages/Default", QString()).toString();

	return l;
}

PluginVoiceCommand::~PluginVoiceCommand() {}

void PluginVoiceCommand::InitApiCalls()
{
	DECLARE_PLUGIN_API_CALL("addbunny(sn)", &PluginVoiceCommand::Api_AddAuthorizedBunny);
	DECLARE_PLUGIN_API_CALL("rmbunny(sn)", &PluginVoiceCommand::Api_RemoveAuthorizedBunny);
	DECLARE_PLUGIN_API_CALL("getbunnies()", &PluginVoiceCommand::Api_ListAuthorizedBunnies);
	DECLARE_PLUGIN_API_CALL("bunny()", &PluginVoiceCommand::Api_Bunny);
	DECLARE_PLUGIN_API_CALL("language()", &PluginVoiceCommand::Api_Language);
	DECLARE_PLUGIN_API_CALL("words()", &PluginVoiceCommand::Api_Words);
	DECLARE_PLUGIN_API_CALL("sentences()", &PluginVoiceCommand::Api_Sentences);
	DECLARE_PLUGIN_API_CALL("key()", &PluginVoiceCommand::Api_Key);
	DECLARE_PLUGIN_BUNNY_API_CALL("key()", &PluginVoiceCommand::Api_BunnyKey);
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_AddAuthorizedBunny)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString hSn = hRequest.GetArg("sn");
	QStringList bunnies = GetSettings("Bunnies", QStringList()).toStringList();
	bunnies << hSn;
	bunnies.removeDuplicates();
	SetSettings("Bunnies", bunnies);
	return new ApiManager::ApiOk(Translator::tr("Bunny '%1' added", account).arg(hSn));
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_RemoveAuthorizedBunny)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString hSn = hRequest.GetArg("sn");
	QStringList bunnies = GetSettings("Bunnies", QStringList()).toStringList();
	bunnies.removeAll(hSn);
	bunnies.removeDuplicates();
	SetSettings("Bunnies", bunnies);
	return new ApiManager::ApiOk(Translator::tr("Bunny '%1' removed", account).arg(hSn));
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_ListAuthorizedBunnies)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	return new ApiManager::ApiList(GetSettings("Bunnies", QStringList()).toStringList());
}

PLUGIN_BUNNY_API_CALL(PluginVoiceCommand::Api_BunnyKey)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "add")
	{
		QStringList list = bunny->GetPluginSetting(GetName(), "GoogleKeys", QStringList()).toStringList();
		if(!hRequest.HasArg("key"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("key", GetName()));
		QString key = hRequest.GetArg("key");

		if(!list.contains(key))
		{
			list.append(key);
			list.removeDuplicates();
			bunny->SetPluginSetting(GetName(), "GoogleKeys", list);
			return new ApiManager::ApiOk(Translator::tr("Key '%1' added for bunny '%2'", account).arg(key, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Key '%1' already exists for bunny '%2'", account).arg(key, QString(bunny->GetID())));
	}
	else if(action == "remove")
	{
		QStringList list = bunny->GetPluginSetting(GetName(), "GoogleKeys", QStringList()).toStringList();
		if(!hRequest.HasArg("key"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("key", GetName()));
		QString key = hRequest.GetArg("key");

		if(list.contains(key))
		{
			list.removeAll(key);
			list.removeDuplicates();
			bunny->SetPluginSetting(GetName(), "GoogleKeys", list);
			return new ApiManager::ApiOk(Translator::tr("Key '%1' removed for bunny '%2'", account).arg(key, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Key '%1' does not exist for bunny '%2'", account).arg(key, QString(bunny->GetID())));
	}
	else if(action == "list")
	{
		QStringList list = bunny->GetPluginSetting(GetName(), "GoogleKeys", QStringList()).toStringList();
        	return new ApiManager::ApiList(list);
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_Key)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "bunnies")
	{
		QMap<QString, QVariant> list;

		//foreach(QString key, codes)
		{
		//	list.insert(key, GetSettings("Languages/" + key, QString()).toString());
		}
        	return new ApiManager::ApiMappedList(list);
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("key"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("key", GetName()));

		QString key = hRequest.GetArg("key");

		QStringList list = GetSettings("GoogleKeys", QStringList()).toStringList();

		if(!list.contains(key))
		{
			list.append(key);
			list.removeDuplicates();
			SetSettings("GoogleKeys", list);
			return new ApiManager::ApiOk(Translator::tr("Key '%1' added for server", account).arg(key));
		}
		return new ApiManager::ApiError(Translator::tr("Key '%1' already exists", account).arg(key));
	}
	else if(action == "remove")
	{
		if(!hRequest.HasArg("key"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("key", GetName()));

		QString key = hRequest.GetArg("key");

		QStringList list = GetSettings("GoogleKeys", QStringList()).toStringList();
		if(list.contains(key))
		{
			list.removeAll(key);
			list.removeDuplicates();
			SetSettings("GoogleKeys", list);
			return new ApiManager::ApiOk(Translator::tr("Key '%1' removed for server", account).arg(key));
		}
		return new ApiManager::ApiError(Translator::tr("Key '%1' does not exist for server", account).arg(key));
	}
	else if(action == "list")
	{
		QStringList list = GetSettings("GoogleKeys", QStringList()).toStringList();
        	return new ApiManager::ApiList(list);
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_Bunny)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiList(GetSettings("Bunnies", QStringList()).toStringList());
	}
	else if(action == "del")
	{
		QString hSn = hRequest.GetArg("sn");
		QStringList bunnies = GetSettings("Bunnies", QStringList()).toStringList();
		bunnies.removeAll(hSn);
		bunnies.removeDuplicates();
		SetSettings("Bunnies", bunnies);
		return new ApiManager::ApiOk(Translator::tr("Bunny '%1' removed", account).arg(hSn));
	}
	else if(action == "add")
	{
		QString hSn = hRequest.GetArg("sn");
		QStringList bunnies = GetSettings("Bunnies", QStringList()).toStringList();
		bunnies << hSn;
		bunnies.removeDuplicates();
		SetSettings("Bunnies", bunnies);
		return new ApiManager::ApiOk(Translator::tr("Bunny '%1' added", account).arg(hSn));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_Sentences)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("type"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("type", GetName()));

	QString type = hRequest.GetArg("type");

	if(!hRequest.HasArg("lng"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

	QString lng = hRequest.GetArg("lng");

	if(action == "list")
	{
        	return new ApiManager::ApiList(GetSettings(lng + "/" + type, QStringList()).toStringList());
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("word"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("word", GetName()));

		QString word = hRequest.GetArg("word");

		QStringList words = GetSettings(lng + "/" + type, QStringList()).toStringList();
		if(words.contains(word))
		{
			words.removeAll(word);
			SetSettings(lng + "/" + type, words);
		}
		return new ApiManager::ApiOk(Translator::tr("Word '%1' is now deleted").arg(word));
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("word"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("word", GetName()));

		QString word = hRequest.GetArg("word");

		QStringList words = GetSettings(lng + "/" + type, QStringList()).toStringList();
		if(!words.contains(word))
		{
			words << word;
			SetSettings(lng + "/" + type, words);
		}
		return new ApiManager::ApiOk(Translator::tr("Word '%1' is now added").arg(word));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_Words)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("type"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("type", GetName()));

	QString type = hRequest.GetArg("type");

	if(!hRequest.HasArg("lng"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

	QString lng = hRequest.GetArg("lng");

	if(action == "list")
	{
        	return new ApiManager::ApiList(GetSettings(lng + "/" + type, QStringList()).toStringList());
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("word"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("word", GetName()));

		QString word = hRequest.GetArg("word");

		QStringList words = GetSettings(lng + "/" + type, QStringList()).toStringList();
		if(words.contains(word))
		{
			words.removeAll(word);
			SetSettings(lng + "/" + type, words);
		}
		return new ApiManager::ApiOk(Translator::tr("Word '%1' is now deleted").arg(word));
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("word"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("word", GetName()));

		QString word = hRequest.GetArg("word");

		QStringList words = GetSettings(lng + "/" + type, QStringList()).toStringList();
		if(!words.contains(word))
		{
			words << word;
			SetSettings(lng + "/" + type, words);
		}
		return new ApiManager::ApiOk(Translator::tr("Word '%1' is now added").arg(word));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginVoiceCommand::Api_Language)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QStringList codes = GetSettings("Languages/List", QStringList()).toStringList();
		QMap<QString, QVariant> list;

		foreach(QString key, codes)
		{
			list.insert(key, GetSettings("Languages/" + key, QString()).toString());
		}
        	return new ApiManager::ApiMappedList(list);
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("lng"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

		QString lng = hRequest.GetArg("lng");

		RemoveSettings("Languages/" + lng);
		QStringList codes = GetSettings("Languages/List", QStringList()).toStringList();
		if(codes.contains(lng))
		{
			codes.removeAll(lng);
			SetSettings("Languages/List", codes);
		}
		return new ApiManager::ApiOk(Translator::tr("Bunny language '%1' is now deleted").arg(lng));
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("lng"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

		QString lng = hRequest.GetArg("lng");

		if(!hRequest.HasArg("equiv"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("equiv", GetName()));

		QString equiv = hRequest.GetArg("equiv");

		SetSettings("Languages/" + lng, equiv);
		QStringList codes = GetSettings("Languages/List", QStringList()).toStringList();
		if(!codes.contains(lng))
		{
			codes << lng;
			SetSettings("Languages/List", codes);
		}
		return new ApiManager::ApiOk(Translator::tr("Bunny language '%1' is '%2'").arg(lng, equiv));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

