#include <QDateTime>
#include <QRegExp>
#include <QMapIterator>
#include <QXmlStreamReader>
#include <QTimer>
#include <memory>
#include "bunny.h"
#include "bunnymanager.h"
#include "httprequest.h"
#include "log.h"
#include "cron.h"
#include "messagepacket.h"
#include "plugin_rss.h"
#include "settings.h"
#include "plugininterface.h"
#include "translator.h"
#include "ttsmanager.h"

PluginRss::PluginRss():PluginInterface("rss", "RSS Reader", BunnyV2Plugin | CronPlugin | RfidPlugin | SingleClickPlugin | PremiumPlugin | VoicePlugin | MessagePlugin | DevPlugin )
{
}

PluginRss::~PluginRss()
{
	Cron::UnregisterAll(this);
}

void PluginRss::readFeeds(Bunny * b, bool force)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Feeds", QMap<QString, QVariant>()).toMap();
	//Log::LogInfo("Read feeds for " + b->GetBunnyName());
	QMap<QString, QVariant>::iterator i;
	for (i = list.begin(); i != list.end(); ++i)
	{
		readFeed(i.value().toString(), b, force);
	}
	//Log::LogInfo("Finish reading feeds for " + b->GetBunnyName());
}

void PluginRss::readFeeds(Bunny * b)
{
	readFeeds(b, false);
}

void PluginRss::readFeed(QString feed, Bunny * b)
{
	readFeed(feed, b, false);
}

void PluginRss::readFeed(QString feed, Bunny * b, bool)
{
	QUrl url(feed);
	QsLogging::Logger::DebugLog(QString("GET %1").arg(url.toString()), GetName());
	QNetworkAccessManager *manager = new QNetworkAccessManager(this);
	connect(manager, SIGNAL(finished(QNetworkReply*)),this, SLOT(analyseXml(QNetworkReply*)));
	manager->setProperty("bunny", b->GetID());
	manager->setProperty("feed", feed);
	manager->get(QNetworkRequest(url));
}

void PluginRss::analyseXml(QNetworkReply* networkReply)
{
	if (!networkReply->error())
	{
		QString reply = networkReply->readAll();
		Bunny * bunny = BunnyManager::GetBunny(networkReply->parent()->property("bunny").toByteArray());
		QString feed = networkReply->parent()->property("feed").toString();

		QsLogging::Logger::DebugLog(QString("%1 for %2").arg(QString("Parser"), QString(bunny->GetID())), GetName());
		PluginRss_Parser * p = new PluginRss_Parser(this, feed, reply, bunny);
		connect(p, SIGNAL(finished()), p, SLOT(deleteLater()));
		connect(p, SIGNAL(done(bool,Bunny *,QStringList, QString)), this, SLOT(analyseDone(bool,Bunny *,QStringList, QString)));
		p->start();
	}
	else
	{
		LogError("Can't read RSS feed");
	}
	networkReply->deleteLater();
	networkReply->parent()->deleteLater();
}

void PluginRss::analyseDone(bool ret, Bunny * b, QStringList list, QString feed)
{
	if(ret && list.length())
	{
		QsLogging::Logger::DebugLog(QString("%1 for %2").arg(QString("Player"), QString(b->GetID())), GetName());
		QStringList associations = b->GetPluginSetting(GetName(), "ReplacesAssociation", QMap<QString, QVariant>()).toMap().value(feed, QVariant()).toString().split(",");
		QStringList final;
		foreach(QString message, list)
		{
			if(associations.length())
			{
				LogDebug("Before : " + message);
				QMap<QString, QVariant> replaces = b->GetPluginSetting(GetName(), "Replaces", QMap<QString, QVariant>()).toMap();
				foreach(QString association, associations)
				{
					QStringList replacement = replaces.value(association, QString()).toString().split("!!SEP!!");
					if(replacement.length() == 2)
					{
						QString pattern = replacement.at(0);
						pattern.replace("\\", "\\\\");
						QString replace = replacement.at(1);
						message.replace(QRegExp(pattern), replace);
					}
				}
				//message.replace(QRegExp("\\([^\\)]+\\):"), ".");
				//message.replace(QRegExp("^[^\\-]+ -"), "");
				//message.replace(QRegExp("#"), "");
				LogDebug("After  : " + message);
			}
			final.append(message);
		}
		PluginRss_Player * p = new PluginRss_Player(this, b, final, true);
		connect(p, SIGNAL(finished()), p, SLOT(deleteLater()));
		connect(p, SIGNAL(done(bool,Bunny*,QStringList,bool)), this, SLOT(playerDone(bool,Bunny*,QStringList,bool)));
		p->start();
	}
}

void PluginRss::playerDone(bool ret, Bunny * b, QStringList files, bool save)
{
	if(ret)
	{
		QByteArray message;
		foreach(QString file, files)
		{
			message += "MU " + file.toLatin1() + "\nMW\n";
		}
		if(save)
		{
			SaveMessage(b, files);
		}
		if(b->IsConnected())
			b->SendPacket(MessagePacket(message), GetName());
	}
}

void PluginRss::OnCron(Bunny * b, QVariant, unsigned int)
{
	if(!b->IsSleeping())
		readFeeds(b);
}

bool PluginRss::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("feed,rss", b), command))
	{
		readFeeds(b);
		return true;
	}
	return false;
}

bool PluginRss::OnRFID(Bunny * b, QByteArray const& tag)
{
	QString rfid = b->GetPluginSetting(GetName(), "RFID", QString()).toString();
	if(rfid.toLatin1() == tag)
	{
		readFeeds(b);
		return true;
	}
	return false;
}

bool PluginRss::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	if (type == PluginInterface::SingleClick) {
		readFeeds(b);
		return true;
	}
	return false;
}

void PluginRss::CleanCrons(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginRss::RegisterCrons(Bunny * b)
{
	Cron::Register(this, 5, 0, 0, b, Cron::Classic, QVariant() );
/*
	int frequency = b->GetPluginSetting(GetName(), "Frequency", 0).toInt();
	if(frequency > 0)
	{
		Cron::Register(this, frequency, 0, 0, b, QVariant() );
	}
*/
/*
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext()) {
		i.next();
		QString time = i.key();
		QString url = i.value().toString();
		Cron::RegisterDaily(this, Cron::mkTime(time), b, QVariant());
	}
*/
}

void PluginRss::OnBunnyConnect(Bunny * b)
{
/*
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Feeds", QMap<QString, QVariant>()).toMap();
	QMap<QString, QVariant>::iterator i;
	for (i = list.begin(); i != list.end(); ++i)
	{
		b->SetPluginSetting(GetName(), "Last/" + i.value().toString(), QString());
	}
*/
	RegisterCrons(b);
}

void PluginRss::OnBunnyDisconnect(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}


void PluginRss::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("schedule()", PluginRss, Api_Schedule);
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", PluginRss, Api_RFID);
	DECLARE_PLUGIN_BUNNY_API_CALL("feed()", PluginRss, Api_Feed);
	DECLARE_PLUGIN_BUNNY_API_CALL("custom()", PluginRss, Api_Custom);
	DECLARE_PLUGIN_BUNNY_API_CALL("language()", PluginRss, Api_Language);
}

PLUGIN_BUNNY_API_CALL(PluginRss::Api_Custom)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Replaces", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "listassociate")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "ReplacesAssociation", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "associate")
	{
		if(!hRequest.HasArg("feed"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("feed", GetName()));

		QString feed = hRequest.GetArg("feed");

		if(!hRequest.HasArg("replace"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("replace", GetName()));

		QString replace = hRequest.GetArg("replace");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "ReplacesAssociation", QMap<QString, QVariant>()).toMap();
		QStringList associations = list.value(feed, QVariant()).toString().split(",");
		associations.append(replace);
		associations.sort();
		associations.removeDuplicates();
		list.insert(feed, associations.join(","));
		bunny->SetPluginSetting(GetName(), "ReplacesAssociation", list);
		return new ApiManager::ApiOk(Translator::tr("Associate '%1' replacement for '%2' for bunny '%3'").arg(replace, feed, QString(bunny->GetID())));
	}
	else if(action == "dissociate")
	{
		if(!hRequest.HasArg("feed"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("feed", GetName()));

		QString feed = hRequest.GetArg("feed");

		if(!hRequest.HasArg("replace"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("replace", GetName()));

		QString replace = hRequest.GetArg("replace");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "ReplacesAssociation", QMap<QString, QVariant>()).toMap();
		QStringList associations = list.value(feed, QVariant()).toString().split(",");
		associations.removeAll(replace);
		list.insert(feed, associations.join(","));
		bunny->SetPluginSetting(GetName(), "ReplacesAssociation", list);
		return new ApiManager::ApiOk(Translator::tr("Associate '%1' replacement for '%2' for bunny '%3'").arg(replace, feed, QString(bunny->GetID())));
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		if(!hRequest.HasArg("pattern"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("pattern", GetName()));

		QString pattern = hRequest.GetArg("pattern");

		if(!hRequest.HasArg("replace"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("replace", GetName()));

		QString replace = hRequest.GetArg("replace");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Replaces", QMap<QString, QVariant>()).toMap();
		if(!list.contains(name))
		{
			QString custom = pattern + "!!SEP!!" + replace;
			list.insert(name, custom);
			bunny->SetPluginSetting(GetName(), "Replaces", list);
			return new ApiManager::ApiOk(Translator::tr("Add replacement for '%1' to '%2' for bunny '%3'").arg(pattern, replace, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Replace '%1' already exist for bunny '%2'", account).arg(name, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Replaces", QMap<QString, QVariant>()).toMap();
		if(list.contains(name))
		{
			list.remove(name);
			bunny->SetPluginSetting(GetName(), "Replaces", list);

			return new ApiManager::ApiOk(Translator::tr("Replace '%1' removed for bunny '%2'", account).arg(name, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Replace '%1' does not exist for bunny '%2'", account).arg(name, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginRss::Api_Feed)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Feeds", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		QString feed;
		if(hRequest.HasArg("feed"))
			feed = hRequest.GetArg("feed");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Feeds", QMap<QString, QVariant>()).toMap();
		if(!list.contains(name))
		{
			list.insert(name, feed);
			bunny->SetPluginSetting(GetName(), "Feeds", list);
			return new ApiManager::ApiOk(Translator::tr("Add feed '%1' for bunny '%2'").arg(feed, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Feed '%1' already exist for bunny '%2'", account).arg(name, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Feeds", QMap<QString, QVariant>()).toMap();
		if(list.contains(name))
		{
			list.remove(name);
			bunny->SetPluginSetting(GetName(), "Feeds", list);

			return new ApiManager::ApiOk(Translator::tr("Feed '%1' removed for bunny '%2'", account).arg(name, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Feed '%1' does not exist for bunny '%2'", account).arg(name, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginRss::Api_RFID)
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

		QString name;
		if(hRequest.HasArg("name"))
			name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, name);
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

PLUGIN_BUNNY_API_CALL(PluginRss::Api_Schedule)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		QString name;
		if(hRequest.HasArg("name"))
			name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(!list.contains(time))
		{
			Cron::RegisterDaily(this, Cron::mkTime(time), bunny, Cron::Classic, QVariant::fromValue(name));
			list.insert(time, name);
			bunny->SetPluginSetting(GetName(), "Schedules", list);
			return new ApiManager::ApiOk(Translator::tr("Add schedule at '%1' to bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("Schedule at '%1' already exists for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("time"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("time", GetName()));

		QString time = hRequest.GetArg("time");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Schedules", QMap<QString, QVariant>()).toMap();
		if(list.contains(time))
		{
			list.remove(time);
			bunny->SetPluginSetting(GetName(), "Schedules", list);

        		OnBunnyDisconnect(bunny);
        		OnBunnyConnect(bunny);

			return new ApiManager::ApiOk(Translator::tr("Schedule at '%1' removed for bunny '%2'", account).arg(time, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("No schedule at '%1' for bunny '%2'", account).arg(time, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginRss::Api_Language)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiList(GetLanguages());
	}
	else if(action == "get")
	{
		return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "Language", bunny->GetLanguage()).toString());
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("lng"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("lng", GetName()));

		QString lng = hRequest.GetArg("lng");

		if(!GetLanguages().contains(lng))
			return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("lng", GetName()));

		bunny->SetPluginSetting(GetName(), "Language", lng);
		return new ApiManager::ApiOk(Translator::tr("Bunny language is now '%1' for plugin '%2'").arg(lng, GetName()));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
/*
PLUGIN_BUNNY_API_CALL(PluginRss::Api_setFrequency)
{
	Q_UNUSED(account);

	if(!hRequest.HasArg("min"))
		return new ApiManager::ApiError(QString("Missing argument 'min' for plugin Colissimo"));

	int min = hRequest.GetArg("min").toInt();
	if(min !=0 && (min < 10 || min > 1440))
		return new ApiManager::ApiError(QString("Choose between 10-1440 minutes"));

	bunny->SetPluginSetting(GetName(), "Frequency", min);
	CleanCrons(bunny);
	RegisterCrons(bunny);
	return new ApiManager::ApiOk(QString("New frequency defined (%1min) for bunny '%2'").arg(hRequest.GetArg("min"), QString(bunny->GetID())));
}

PLUGIN_BUNNY_API_CALL(PluginRss::Api_getFrequency)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiManager::ApiString(QString::number(bunny->GetPluginSetting(GetName(), "Frequency", 0).toInt()));
}

PLUGIN_BUNNY_API_CALL(PluginRss::Api_instantTracking)
{
	Q_UNUSED(account);
	CallColissimo(bunny, hRequest.GetArg("code").toUpper(), true);

	return new ApiManager::ApiOk(QString("Starting tracking for bunny '%1', package %2").arg(QString(bunny->GetID()), hRequest.GetArg("code").toUpper()));
}
*/
/* PARSER THREAD */
PluginRss_Parser::PluginRss_Parser(PluginRss * p, QString f, QString s, Bunny * b):plugin(p),feed(f),content(s),bunny(b)
{
}

void PluginRss_Parser::run()
{
// <rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">
/*
rss
	channel
		item
			title
			pubDate
			guid
*/
// <feed xmlns="http://www.w3.org/2005/Atom">
/*
feed
	entry
		title
		updated
		id
*/
	QXmlStreamReader xml;
	xml.clear();
	xml.addData(QString::fromUtf8(content.toLatin1()));

	QString channelKey;
	QString itemKey;
	QString titleKey;
	QString dateKey;
	QString idKey;

	QRegExp rx("<rss");
	if (rx.indexIn(content, 0) != -1)
	{
		channelKey = "channel";
		itemKey = "item";
		titleKey = "title";
		dateKey = "pubDate";
		idKey = "guid";
	}
	else
	{
		channelKey = "feed";
		itemKey = "entry";
		titleKey = "title";
		dateKey = "updated";
		idKey = "id";
	}
	bool error = false;

	bool inChannel = false;
	bool inItem = false;

	QString currentTag;
	QString id;
	QString date;
	QString title;

	QStringList ids;
	QStringList titles;
	QStringList dates;

	while ( !xml.atEnd() )
	{
		xml.readNext();
		if (xml.isStartElement())
		{
			currentTag = xml.name().toString();
			if(currentTag == channelKey)
			{
				inChannel = true;
			}
			if(inChannel && currentTag == itemKey)
			{
				inItem = true;
			}
		}
		else if (xml.isEndElement())
		{
			currentTag = xml.name().toString();
			if(currentTag == channelKey)
			{
				inChannel = false;
			}
			if(inChannel && currentTag == itemKey)
			{
				inItem = false;
				ids << id;
				dates << date;
				titles << title;
			}
		}
		else if (xml.isCharacters() && !xml.isWhitespace())
		{
			if (inItem && currentTag == titleKey)
			{
				title = xml.text().toString().trimmed();
			}
			if (inItem && currentTag == dateKey)
			{
				date = xml.text().toString().trimmed();
			}
			if (inItem && currentTag == idKey)
			{
				id = xml.text().toString().trimmed();
			}
		}
	}

	QStringList messages;
	if(ids.size() > 0)
	{
		QStringList last = bunny->GetPluginSetting(plugin->GetName(), "Last/" + feed, QStringList()).toStringList();
		QStringList newLast;
		for (int i = 0; i < ids.size(); ++i)
		{
			if(!last.contains(ids.at(i) + "/" + dates.at(i)))
			{
				messages.append(titles.at(i));
			}
			newLast.append(ids.at(i) + "/" + dates.at(i));
		}
		bunny->SetPluginSetting(plugin->GetName(), "Last/" + feed, newLast);
	}

	if (xml.error() && xml.error() != QXmlStreamReader::PrematureEndOfDocumentError)
		error = true;

	if(error)
	{
		LogDebug("Parser error");
		emit done(false, bunny, QStringList(), QString());
	}
	else
	{
		emit done(true, bunny, messages, feed);
	}
}

/* PLAYER THREAD */
PluginRss_Player::PluginRss_Player(PluginRss * p, Bunny * bu, QStringList t, bool s):plugin(p),bunny(bu),titles(t),save(s)
{
}

void PluginRss_Player::run()
{
	QStringList messages;

	foreach(QString title, titles)
	{
		TTSAnswer file = TTSManager::CreateSound(title, bunny->GetVoice(), bunny->GetLanguage());
		plugin->TTSLog(bunny->GetID(), plugin->GetName(), file);
		messages.append(file.file);
	}

	if(messages.count() > 0)
	{
		emit done(true, bunny, messages, save);
	}
	else
	{
		emit done(false, bunny, messages, save);
	}
}
