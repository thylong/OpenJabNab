#include <QDateTime>
#include <QRegExp>
#include <QTimer>
#include <QCryptographicHash>
#include <QNetworkRequest>
#include <QNetworkReply>
#include <QMapIterator>
#include <QRegExp>
#include <memory>
#include "browserclient.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "httprequest.h"
#include "log.h"
#include "cron.h"
#include "messagepacket.h"
#include "plugin_needtoknow.h"
#include "settings.h"
#include "ttsmanager.h"
#include "translator.h"

#define RANDOMIZEDRATIO 20

PluginNeedtoknow::PluginNeedtoknow()
: PluginInterface("needtoknow", "Need to know", BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin | CronPlugin | RfidPlugin | MessagePlugin | ApiPlugin)
{
}

QString PluginNeedtoknow::OnApiGet(Bunny *b, QVariant v)
{
	QString language = v.value<QString>().trimmed();
	if(language == "")
	{
		language = b->GetLanguage();
	}
	getNTKPage(b, language, true);
	return QString();
}

PluginNeedtoknow::~PluginNeedtoknow()
{
}

int PluginNeedtoknow::GetRandomizedDelay(unsigned int delay)
{
	int deviation = 0;

	if(RANDOMIZEDRATIO > 0 && RANDOMIZEDRATIO < 100)
	{
		unsigned int maxDeviation = (delay * 2 * RANDOMIZEDRATIO) / 100;
		if(maxDeviation > 0)
		{
			deviation = qrand() % (maxDeviation);
		}
		deviation -= (maxDeviation/2);
	}
	int ret = delay + deviation;
	if(ret < 3)
	{
		LogDebug("New knowing in less than 3 minutes : " + QString::number(ret));
	}
	return qMax(3, ret);
}

void PluginNeedtoknow::createCron(Bunny * b, int delay, QString language)
{
	if(!delay)
	{
		delay = 60;
	}
	int iDelay = GetRandomizedDelay(delay);
	Cron::RegisterOneShot(this, iDelay, b, Cron::Random, language, NULL);
}

void PluginNeedtoknow::OnCron(Bunny * b, QVariant v, unsigned int type)
{
	QString language = v.value<QString>().trimmed();
	getNTKPage(b, language, true);
	if(type == Cron::Random)
	{
		QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Delays", QMap<QString, QVariant>()).toMap();
		if(list.contains(language))
		{
			int delay = list.value(language).toInt();
			createCron(b, delay, language);
		}
	}
}

bool PluginNeedtoknow::OnRFID(Bunny * b, QByteArray const& tag)
{
	QString language = b->GetPluginSetting(GetName(), QString("RFID/%1").arg(QString(tag.toHex())), QString()).toString();
	if(language != "")
	{
		getNTKPage(b, language);
		return true;
	}
	return false;
}

bool PluginNeedtoknow::OnClick(Bunny * b, PluginInterface::ClickType)
{
	getNTKPage(b);
	return true;
}

bool PluginNeedtoknow::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if(getPertinence(Translator::tr("know", b->GetLanguage()), command))
	{
		getNTKPage(b);
		return true;
	}
	return false;
}

void PluginNeedtoknow::getNTKPage(Bunny * b)
{
	getNTKPage(b, b->GetLanguage(), false);
}

void PluginNeedtoknow::getNTKPage(Bunny * b, QString language)
{
	getNTKPage(b, language, false);
}

void PluginNeedtoknow::getNTKPage(Bunny * b, QString language, bool save)
{
	QUrl url("https://www.savoir-inutile.com/");
	QsLogging::Logger::DebugLog(QString("GET %1").arg(url.toString()), GetName());
	BrowserClient *manager = new BrowserClient(this);
  PluginNTK_WORKER *p = new PluginNTK_WORKER(this, b, language, save);
  QObject::connect(p, &PluginNTK_WORKER::done, this, &PluginNeedtoknow::analyseDone);
  QObject::connect(manager, &BrowserClient::finished, p, &PluginNTK_WORKER::requestFinished);
  p->start();

	manager->get(QNetworkRequest(url));
}


void PluginNeedtoknow::analyseDone(bool ret, Bunny * b, QStringList files, bool save)
{
	if(ret)
	{
		if(b && b->IsIdle())
		{
			QByteArray message;
			foreach(QString file, files)
			{
				message += "MU " + file + "\nMW\n";
			}
			b->SendPacket(MessagePacket(message), GetName());
			if(save)
			{
				SaveMessage(b, files);
			}
		}
	}
}

void PluginNeedtoknow::OnBunnyConnect(Bunny * b)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext())
	{
		i.next();
		QString time = i.key();
		QString language = i.value().toString();
		Cron::RegisterDaily(this, Cron::mkTime(time), b, Cron::Classic, QVariant::fromValue(language));
	}
	list = b->GetPluginSetting(GetName(), "Delays", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> j(list);
	while (j.hasNext())
	{
		j.next();
		QString language = j.key();
		int delay = GetRandomizedDelay(j.value().toInt());
		Cron::RegisterOneShot(this, delay, b, Cron::Random, language, NULL);
	}
}

void PluginNeedtoknow::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginNeedtoknow::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", PluginNeedtoknow, Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", PluginNeedtoknow, Api_RFID);
	DECLARE_PLUGIN_API_CALL("language()", PluginNeedtoknow, Api_Language);
}

PLUGIN_BUNNY_API_CALL(PluginNeedtoknow::Api_RFID)
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

		if(!hRequest.HasArg("language"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("language", GetName()));

		QString language = hRequest.GetArg("language");
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, language);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Associate(bunny, this);
			return new ApiManager::ApiOk(Translator::tr("Add RFID '%1' for bunny '%2'").arg(tag, QString(bunny->GetID())));
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

PLUGIN_BUNNY_API_CALL(PluginNeedtoknow::Api_Schedule)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "listdelay")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Delays", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "adddelay")
	{
		if(!hRequest.HasArg("delay"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("delay", GetName()));

		int hDelay = hRequest.GetArg("delay").toInt();
		if(hDelay < 3)
		{
			hDelay = 3;
		}
		QString sDelay = QString::number(hDelay);

		if(!hRequest.HasArg("language"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("language", GetName()));

		QString language = hRequest.GetArg("language");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Delays", QMap<QString, QVariant>()).toMap();
		if(!list.contains(language))
		{
			list.insert(language, sDelay);
			bunny->SetPluginSetting(GetName(), "Delays", list);
			OnBunnyDisconnect(bunny);
			OnBunnyConnect(bunny);
			return new ApiManager::ApiOk(Translator::tr("Add schedule for '%1' for bunny '%2'", account).arg(language, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Schedule already exists for bunny '%2'", account).arg(QString(bunny->GetID())));
	}
	else if(action == "deldelay")
	{
		if(!hRequest.HasArg("language"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("language", GetName()));

		QString language = hRequest.GetArg("language");
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Delays", QMap<QString, QVariant>()).toMap();
		if(list.contains(language))
		{
			list.remove(language);
			bunny->SetPluginSetting(GetName(), "Delays", list);

			// Recreate crons
			OnBunnyDisconnect(bunny);
			OnBunnyConnect(bunny);
			return new ApiManager::ApiOk(Translator::tr("Remove schedule for '%1' for bunny '%2'", account).arg(language, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("No schedule for '%1' for bunny '%2'", account).arg(language, QString(bunny->GetID())));
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString hTime = hRequest.GetArg("time");

		if(!hRequest.HasArg("language"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("language", GetName()));

		QString language = hRequest.GetArg("language");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(!list.contains(hTime))
		{
			Cron::RegisterDaily(this, Cron::mkTime(hTime), bunny, Cron::Classic, QVariant::fromValue(language));
			list.insert(hTime,language);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			return new ApiManager::ApiOk(Translator::tr("Add schedule for '%2' at '%1' for bunny '%3'", account).arg(hTime, language, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Schedule already exists at '%1' for bunny '%2'", account).arg(hTime, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		QString time = hRequest.GetArg("time");
		if(list.contains(time))
		{
			list.remove(time);
			bunny->SetPluginSetting(GetName(), "Schedules", list);

			// Recreate crons
			OnBunnyDisconnect(bunny);
			OnBunnyConnect(bunny);
			return new ApiManager::ApiOk(Translator::tr("Remove schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginNeedtoknow::Api_Language)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(Translator::GetLanguageList(GetLanguages(), account.GetLanguage()));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

/* WORKER THREAD */
PluginNTK_WORKER::PluginNTK_WORKER(PluginNeedtoknow * p, Bunny * bu, QString lng, bool s)
  : plugin(p)
  , bunny(bu)
  , language(lng)
  , save(s)
{
}

void PluginNTK_WORKER::requestFinished(QNetworkReply* rep)
{
  //QsLogging::Logger::DebugLog(QString("%1 for %2, %3").arg(QString("NTKWorker"), QString(bunny->GetID()), language), plugin->GetName());
  if (!rep->error())
  {
    QStringList files;

    if(language != "")
    {
      QString buf = rep->readAll();
      QRegExp rx("<h2 id=\"phrase\"[^>]*>([^<]+)</h2>");
      rx.setMinimal(true);
      int pos = 0;
      if((pos = rx.indexIn(buf, pos)) != -1 )
      {
//        QRegExp rx2("\\([^\\)]+\\)");
        QString text = rx.cap(1).trimmed();//.replace(rx2, "").replace("  ", " ");

        TTSAnswer q = TTSManager::CreateSound(text, bunny->GetVoice(), bunny->GetLanguage());
        plugin->TTSLog(bunny->GetID(), plugin->GetName(), q);
        files.append(q.file);
        emit done(true, bunny, files, save);
      }
      else
      {
        emit done(false, bunny, QStringList(), save);
      }
    }
    else
    {
      emit done(false, bunny, QStringList(), save);
    }
	}
	rep->deleteLater();
	rep->parent()->deleteLater();
  this->quit();
}
