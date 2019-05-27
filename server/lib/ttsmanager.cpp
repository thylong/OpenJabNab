#include <QCryptographicHash>
#include <QDataStream>
#include <QProcess>
#include <QEventLoop>
#include <QFile>
#include <QObject>
#include <QPluginLoader>
#include <QStringList>
#include <QUrl>
#include <QDir>
#include "apimanager.h"
#include "log.h"
#include "QsLog.h"
#include "settings.h"
#include "translator.h"
#include "ttsmanager.h"
#include <cstdlib>

TTSManager::TTSManager()
{
  ttsDir = QDir(GlobalSettings::GetString("Directories/TTSLibDir",
                QCoreApplication::applicationDirPath().append("/lib/tts/")
               ));
	ttsDir.setNameFilters(QStringList("*.so"));

	if (!ttsDir.exists())
	{
		LogError("Unable to open tts directory !\n");
		exit(-1);
	}

  QString pcPath = GlobalSettings::GetConfigDir().append("/tts/");
  QDir ttsConfDir = QDir(pcPath);
  if(!ttsConfDir.exists())
  {
    if(!ttsConfDir.mkpath(pcPath))
    {
      LogError("Unable to create tts config directory !\n");
			exit(-1);
    }
  }
}

TTSManager & TTSManager::Instance()
{
	static TTSManager p;
	return p;
}

void TTSManager::UnloadTTSs()
{
	foreach(TTSInterface * p, listOfTTSs)
		delete p;

	foreach(QPluginLoader * l, listOfTTSsLoader.values())
	{
		l->unload();
		delete l;
	}
}

Voice TTSManager::GetVoice(QString voice)
{
	QStringList V = voice.split("/");
	if(V.size() == 2)
	{
		TTSInterface * tts = Instance().GetTTSByName(V.at(0));
		if(tts->GetEnable())
		{
			if(V.size() >=2)
			{
				return tts->GetVoice(V.at(1));
			}
		}
	}
        Voice v;
        v.name = "None";
        v.language = "";
	v.limit = 100;
        return v;
}

QString TTSManager::GetBestVoice(QString voice, QString language)
{
	QStringList V = voice.split("/");
	if(V.size() == 2)
	{
		TTSInterface * tts = Instance().GetTTSByName(V.at(0));
		if(tts->GetEnable())
		{
			if(V.size() >=2)
			{
				Voice v = tts->GetVoice(V.at(1));
				if(v.language == language)
				{
					return voice;
				}
				QStringList voices = tts->GetVoiceList(language);
				foreach(QString name, voices)
				{
					Voice v = tts->GetVoice(name);
					if(v.language == language)
					{
						return voice;
					}
				}
			}
		}
	}
	return voice;
}

QMap<QString, QVariant> TTSManager::GetVoiceList(QString language, bool premium)
{
	QMap<QString, QVariant> list;
	QStringList order;
	if(premium)
	{
		order = GlobalSettings::Get("TTS/orderPremium", QStringList()).toStringList();
	}
	else
	{
		order = GlobalSettings::Get("TTS/order", QStringList()).toStringList();
	}
	QStringList approxs = GlobalSettings::Get("TTS/approx", QStringList()).toStringList();
	//LogDebug("TTS : " + order.join(", "));
	foreach(QString ttsname, order)
	{
		//LogDebug("Using : " + ttsname);
		TTSInterface * tts = Instance().GetTTSByName(ttsname);
		QMap<QString, QString> voices = tts->GetVoiceListWithName(language);
		//LogDebug("Voices (" + language + ") : " + QString::number(voices.count()));

		//LogDebug("TTS approx : " + approxs.join(", "));
		foreach(QString approx, approxs)
		{
			QString lng = GlobalSettings::Get("TTSApprox/" + approx, QString()).toString();
			//LogDebug("TTS approx of " + approx + " is " + lng);
			if(lng == language)
			{
				QMap<QString, QString> approxVoices = tts->GetVoiceListWithName(approx);
				QMapIterator<QString, QString> i(approxVoices);
				while (i.hasNext())
				{
					i.next();
					voices.insert(i.key(), i.value());
				}
				//LogDebug("Voices (" + approx + ") : " + QString::number(approxVoices.count()));
			}
			if(approx == language)
			{
				QMap<QString, QString> approxVoices = tts->GetVoiceListWithName(lng);
				QMapIterator<QString, QString> i(approxVoices);
				while (i.hasNext())
				{
					i.next();
					voices.insert(i.key(), i.value());
				}
				//LogDebug("Voices (" + lng + ") : " + QString::number(approxVoices.count()));
			}
		}
		//LogDebug("Voices (" + language + ") : " + QString::number(voices.count()));
		//LogDebug("TTS voices (" + language + "): " + voices.join(", "));
		if(voices.count())
		{
			QMapIterator<QString, QString> i(voices);
			while (i.hasNext())
			{
				i.next();
				list.insert(ttsname + "/" + i.key(), i.value());
			}
			//list.insert(ttsname, voices.join(","));
		}
	}
	return list;
}

void TTSManager::LoadTTSs()
{
	LogInfo(QString("Finding tts in : %1").arg(ttsDir.path()));
	foreach (QFileInfo file, ttsDir.entryInfoList(QDir::Files))
		LoadTTS(file.fileName().toLatin1());
}

bool TTSManager::LoadTTS(QString const& fileName)
{
	if(listOfTTSsByFileName.contains(fileName))
	{
		LogError(QString("TTS '%1' already loaded !").arg(fileName));
		return false;
	}

	QString file = ttsDir.absoluteFilePath(fileName);
	if (!QLibrary::isLibrary(file))
		return false;

	QString status = QString("Loading %1 : ").arg(fileName);

	QPluginLoader * loader = new QPluginLoader(file);
	QObject * p = loader->instance();
	TTSInterface * tts = qobject_cast<TTSInterface *>(p);
	if (tts)
	{
		if(tts->Init() == false)
		{
			delete tts;
			loader->unload();
			delete loader;

			status.append(QString("%1 OK, Initialisation failed").arg(tts->GetName()));
			LogInfo(status);
			return false;
		}

		listOfTTSs.append(tts);
		listOfTTSsFileName.insert(tts, fileName);
		listOfTTSsLoader.insert(tts, loader);
		listOfTTSsByName.insert(tts->GetName(), tts);
		listOfTTSsByFileName.insert(fileName, tts);

		status.append(QString("%1 OK, Enable : %2").arg(tts->GetName(),tts->GetEnable() ? "Yes" : "No"));
		LogInfo(status);
		return true;
	}
	status.append("Failed, ").append(loader->errorString());
	LogInfo(status);
	return false;
}

bool TTSManager::UnloadTTS(QString const& name)
{
	if(listOfTTSsByName.contains(name))
	{
		TTSInterface * p = listOfTTSsByName.value(name);
		QString fileName = listOfTTSsFileName.value(p);
		QPluginLoader * loader = listOfTTSsLoader.value(p);
		listOfTTSsByFileName.remove(fileName);
		listOfTTSsFileName.remove(p);
		listOfTTSsLoader.remove(p);
		listOfTTSsByName.remove(name);
		listOfTTSs.removeAll(p);
		delete p;
		loader->unload();
		delete loader;
		LogInfo(QString("TTS %1 unloaded.").arg(name));
		return true;
	}
	LogInfo(QString("Can't unload tts %1").arg(name));
	return false;
}

bool TTSManager::ReloadTTS(QString const& name)
{
	if(listOfTTSsByName.contains(name))
	{
		TTSInterface * p = listOfTTSsByName.value(name);
		QString file = listOfTTSsFileName.value(p);
		return (UnloadTTS(name) && LoadTTS(file));
	}
	return false;
}

bool TTSManager::ApproxLanguage(QString l1, QString l2)
{
	l1 = GlobalSettings::Get("TTSApprox/" + l1, l1).toString();
	l2 = GlobalSettings::Get("TTSApprox/" + l2, l2).toString();

	if(l1 == l2)
		return true;
	return false;
}

TTSAnswer TTSManager::createSound(TTSInterface * tts, TTSAnswer a, QString text, QString voice, OutputFormat output, bool forceOverwrite)
{
	a.returnedVoice = tts->GetName() + "/" + voice;
	QString file = tts->CreateNewSound(text, voice, forceOverwrite);
    if(file.startsWith("FROM_CACHE"))
	{
		file.replace("FROM_CACHE", "");
		a.cache = true;
	}
	else
	{
		a.cache = false;
	}
	if(output == TTSManager::Format_Adp)
	{
		file = TTSManager::convertToAdp(file);
	}
	a.filePath = file;
	a.file = "broadcast/ojn_local/" + file;
	return a;
}

QString TTSManager::trim(QString text)
{
	QString trimmed = text;
	return trimmed.replace("^[ ]+", " ").replace("[ ]*$", "");
}

QStringList TTSManager::splitForVoice(QString str, QString voice)
{
	Voice v = GetVoice(voice);
	return split(str, v.limit);
}

QStringList TTSManager::split(QString str, int max)
{
	QStringList list;
	if(max < 100)
	{
		max = 100;
	}
	if(str.length() <= max)
	{
		list.append(str);
	}
	else
	{
		while(str.length() > max)
		{
			int cut = TTSManager::findNextCut(str, max);
			list.append(trim(str.left(cut + 1)));
			str = trim(str.mid(cut + 1, str.length())).replace("^[ ]+", "");
		}
		list.append(trim(str));
	}
	return list;
}

int TTSManager::findNextCut(QString str, int max)
{
	QString tmp = str.left(max);
	int point = tmp.lastIndexOf(".");
	if(point > max / 4)
	{
		return point;
	}
	int virgule = tmp.lastIndexOf(";");
	if(virgule > max / 4)
	{
		return virgule;
	}
	int espace = tmp.lastIndexOf(" ");
	return qMax(qMax(espace, virgule), point);
}

TTSAnswer TTSManager::CreateSound(QString text, QString voice, QString language, OutputFormat output, bool forceOverwrite)
{
	text = trim(text);
    	TTSAnswer a;
	a.text = text;
	a.askedLanguage = language;
	a.askedVoice = voice;
	a.overwrite = forceOverwrite;

	QStringList order = GlobalSettings::Get("TTS/orderPremium", QStringList()).toStringList();
	int size = text.length();

	// Favorite language
	if(voice.trimmed() != "")
	{
		QStringList V = voice.split("/");
		if(order.contains(V.at(0)))
		{
			TTSInterface * tts = Instance().GetTTSByName(V.at(0));
			if(tts->GetEnable())
			{
				if(V.size() >=2)
				{
					Voice v = tts->GetVoice(V.at(1));
					if(v.name == V.at(1) && (language == "" || v.language == language || ApproxLanguage(v.language, language)))
					{
						if(v.limit == 0 || v.limit >= size)
						{
							a.returnedLanguage = language;
							return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
						}
					}
				}
			}
		}
	}
	// Same language
	foreach(QString ttsname, order)
	{
		TTSInterface * tts = Instance().GetTTSByName(ttsname);
		if(tts->GetEnable())
		{
			Voice v = tts->GetBestVoice(language);
			if(v.language == language)
			{
				if(v.limit == 0 || v.limit >= size)
				{
					a.returnedLanguage = language;
					return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
				}
			}
		}
	}
	// Approx language
	foreach(QString ttsname, order)
	{
		TTSInterface * tts = Instance().GetTTSByName(ttsname);
		if(tts->GetEnable())
		{
			Voice v = tts->GetBestVoice(language);
			if(ApproxLanguage(v.language, language))
			{
				if(v.limit == 0 || v.limit >= size)
				{
					a.returnedLanguage = v.language;
					return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
				}
			}
		}
	}

	TTSInterface * tts = Instance().GetTTSByName("google");
	a.returnedLanguage = "fr";
	return TTSManager::createSound(tts, a, text, "fr", output, forceOverwrite);
}

TTSAnswer TTSManager::CreateSoundWithGenre(QString text, QString voice, QString language, Voice::VoiceGenre genre, OutputFormat output, bool forceOverwrite)
{
	text = trim(text);
	//DebugLog::Log("TTS", QString("%1, %2, %3, %4").arg(voice, language, QString::number((int)genre), text));
	QStringList order = GlobalSettings::Get("TTS/orderPremium", QStringList()).toStringList();
	int size = text.length();

    	TTSAnswer a;
	a.text = text;
	a.askedLanguage = language;
	a.askedVoice = voice;
	a.overwrite = forceOverwrite;

	// Favorite language
	if(voice.trimmed() != "")
	{
		QStringList V = voice.split("/");
		if(order.contains(V.at(0)))
		{
			TTSInterface * tts = Instance().GetTTSByName(V.at(0));
			if(tts->GetEnable())
			{
				if(V.size() >=2)
				{
					Voice v = tts->GetVoice(V.at(1));
					if(v.name == V.at(1) && (language == "" || v.language == language || ApproxLanguage(v.language, language)))
					{
						if(v.limit == 0 || v.limit >= size)
						{
							a.returnedLanguage = v.language;
							return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
						}
					}
				}
			}
		}
	}
	// Same language and same genre
	foreach(QString ttsname, order)
	{
		TTSInterface * tts = Instance().GetTTSByName(ttsname);
		if(tts->GetEnable())
		{
			Voice v = tts->GetBestVoice(language, genre);
			if(v.language == language && v.genre == genre)
			{
				if(v.limit == 0 || v.limit >= size)
				{
					a.returnedLanguage = v.language;
					return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
				}
			}
		}
	}
	// Same language and other genre
	foreach(QString ttsname, order)
	{
		TTSInterface * tts = Instance().GetTTSByName(ttsname);
		if(tts->GetEnable())
		{
			Voice v = tts->GetBestVoice(language, genre);
			if(v.language == language)
			{
				if(v.limit == 0 || v.limit >= size)
				{
					a.returnedLanguage = v.language;
					return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
				}
			}
		}
	}
	// Approx language and same genre
	foreach(QString ttsname, order)
	{
		TTSInterface * tts = Instance().GetTTSByName(ttsname);
		if(tts->GetEnable())
		{
			Voice v = tts->GetBestVoice(language, genre);
			if(ApproxLanguage(v.language, language) && v.genre == genre)
			{
				if(v.limit == 0 || v.limit >= size)
				{
					a.returnedLanguage = v.language;
					return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
				}
			}
		}
	}
	// Approx language and other genre
	foreach(QString ttsname, order)
	{
		TTSInterface * tts = Instance().GetTTSByName(ttsname);
		if(tts->GetEnable())
		{
			Voice v = tts->GetBestVoice(language, genre);
			if(ApproxLanguage(v.language, language))
			{
				if(v.limit == 0 || v.limit >= size)
				{
					a.returnedLanguage = v.language;
					return TTSManager::createSound(tts, a, text, v.name, output, forceOverwrite);
				}
			}
		}
	}
	TTSInterface * tts = Instance().GetTTSByName("google");
	a.returnedLanguage = "fr";
	return TTSManager::createSound(tts, a, text, "fr", output, forceOverwrite);
}

QString TTSManager::convertToAdp(QString file, bool overwrite, bool fullPath, bool returnFull)
{
    //LogDebug("Need to convert " + file);
    QString fileFS = file;
    if(fileFS.startsWith("broadcast"))
    {
	fileFS.replace("broadcast/ojn_local/", GlobalSettings::Get("Config/RealHttpRoot", QString()).toString());
        fullPath = true;
    }
    if(!fullPath)
    {
        fileFS = GlobalSettings::Get("Config/RealHttpRoot", QString()).toString() + fileFS;
    }

    QString fileWav = "/tmp/" + QCryptographicHash::hash(file.toLatin1(), QCryptographicHash::Md5).toHex() + ".wav";

    QString fileAdpFS = fileFS;
    fileAdpFS.replace(".mp3", ".adp");
    if(!overwrite && QFile::exists(fileAdpFS))
    {
	if(!returnFull)
	{
    		fileAdpFS.replace(GlobalSettings::Get("Config/RealHttpRoot", QString()).toString(), "");
	}
	//LogDebug("=> " + fileAdpFS);
	return fileAdpFS;
    }

// sox /home/prod/OpenJabNab/http-wrapper/ojn_local/tts/acapela/alice/ac5b692798b7329ea3abe6502f806799.mp3 -r 16000 -c 1
/*
    QString program = "/usr/bin/ffmpeg";
    QStringList arguments;
    arguments << "-y";
    arguments << "-i";
    arguments << fileFS;
    arguments << fileWav;
*/
    QString program = "/usr/bin/sox";
    QStringList arguments;
    arguments << fileFS;
    arguments << "-r 16000";
    arguments << "-c 1";
    arguments << fileWav;
    QsLogging::Logger::DebugLog(QString("%1 %2").arg(program, arguments.join(" ")), "TTS");

	char buffer[1024];
	FILE* fd = popen(QString("%1 %2 >/dev/null 2>&1").arg(program, arguments.join(" ")).toLatin1(), "r");
	if (fd != NULL) {

		while(NULL != fgets(buffer, sizeof(buffer), fd)) {
			QString s(buffer);
		}
		pclose (fd);
	}
	//program = "/home/wav2adp";
	program = "/srv/www/ojn/prod/utils/wav24adp2";
	arguments.clear();
	arguments << fileWav;
	arguments << fileAdpFS;
	//arguments << "4ADP2";

    	QsLogging::Logger::DebugLog(QString("%1 %2").arg(program, arguments.join(" ")), "TTS");

	fd = popen(QString("%1 %2 >/dev/null 2>&1").arg(program, arguments.join(" ")).toLatin1(), "r");
	if (fd != NULL) {

		while(NULL != fgets(buffer, sizeof(buffer), fd)) {
			QString s(buffer);
		}
		pclose (fd);
	}
	QFile::remove(fileWav);
	if(!returnFull)
	{
    		fileAdpFS.replace(GlobalSettings::Get("Config/RealHttpRoot", QString()).toString(), "");
	}
	//LogDebug("=> " + fileAdpFS);
	return fileAdpFS;
}

/*******
 * API *
 *******/

void TTSManager::InitApiCalls()
{
	DECLARE_API_CALL("tts()", &TTSManager::Api_TTS);
	DECLARE_API_CALL("voices()", &TTSManager::Api_Voices);
}

API_CALL(TTSManager::Api_TTS)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> list;
/*
		foreach(Bunny * b, listOfBunnies)
		{
			Account * a = AccountManager::GetAccountByLogin(b->GetGlobalSetting("OwnerAccount").toByteArray());
			QString value;
			if(a == NULL) {
				value = QString("n/a");
			} else {
				value = a->GetEmail();
				if(value == QString()) {
					value = QString("nc");
				}
			}
			if(!connected || b->IsConnected())
			{
				list.insert(b->GetID(), value);
			}
		}
*/
		return new ApiManager::ApiMappedList(list);
	}
	else if(action == "reload")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("name"));

		QString name = hRequest.GetArg("name");
		ReloadTTS(name);
		return new ApiManager::ApiString(Translator::tr("TTS %1 is now reloaded", account).arg(name));
	}
	else if(action == "status")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("name"));

		QString name = hRequest.GetArg("name");

		TTSInterface * tts = Instance().GetTTSByName(name);

		if(hRequest.HasArg("status"))
		{
			tts->SetEnable(hRequest.GetArg("status") == "enable" ? true : false);
			return new ApiManager::ApiString(Translator::tr("TTS %1 is now %2", account).arg(name, tts->GetEnable() ? Translator::tr("enabled", account) : Translator::tr("disabled", account)));
		}
		else
		{
			return new ApiManager::ApiOk(Translator::tr("TTS %1 is %2", account).arg(name, tts->GetEnable() ? Translator::tr("enabled", account) : Translator::tr("disabled", account)));
		}
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(TTSManager::Api_Voices)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("tts"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("tts"));

	QString ttsName = hRequest.GetArg("tts");
	TTSInterface * tts = Instance().GetTTSByNameOrNull(ttsName);

	if(tts == NULL)
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("tts"));

	if(action == "list")
	{
		QMap<QString, QMap<QString, QString> > list = tts->GetAllVoices();
		QString voices = "<tts name='"+ttsName+"'>";
		QMap<QString, QMap<QString, QString> >::iterator i;
		for (i = list.begin(); i != list.end(); ++i)
		{
			voices += "<language name='" + i.key() + "'>";
			QMap<QString, QString>::iterator j;
			QMap<QString, QString> l = i.value();
			for (j = l.begin(); j != l.end(); ++j)
			{
				voices += "<voice id='" + j.key() + "'>";
				voices += j.value();
				voices += "</voice>";
			}
			voices += "</languages>";
		}
		voices += "</tts>";
		return new ApiManager::ApiXml(voices);
	}
	else if(action == "status")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("name"));

		QString name = hRequest.GetArg("name");

		TTSInterface * tts = Instance().GetTTSByName(name);

		if(hRequest.HasArg("status"))
		{
			tts->SetEnable(hRequest.GetArg("status") == "enable" ? true : false);
			return new ApiManager::ApiString(Translator::tr("TTS %1 is now %2", account).arg(name, tts->GetEnable() ? Translator::tr("enabled", account) : Translator::tr("disabled", account)));
		}
		else
		{
			return new ApiManager::ApiOk(Translator::tr("TTS %1 is %2", account).arg(name, tts->GetEnable() ? Translator::tr("enabled", account) : Translator::tr("disabled", account)));
		}
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}
