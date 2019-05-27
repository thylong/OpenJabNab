#include <QUrl>
#include <QStringList>

#include "account.h"
#include "accountmanager.h"
#include "apimanager.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "plugininterface.h"
#include "translator.h"
#include "sentencemanager.h"
#include "timezone.h"
#include "httprequest.h"
#include "pluginmanager.h"
#include "ttsmanager.h"
#include "cron.h"

PluginInterface * statsPlugin;

ApiManager::ApiManager()
{
	startTime = QDateTime::currentDateTime().toTime_t();
	statsPlugin = PluginManager::Instance().GetPluginByName("stats");
}

ApiManager & ApiManager::Instance()
{
  static ApiManager a;
  return a;
}

int ApiManager::getUptime()
{
	return QDateTime::currentDateTime().toTime_t() - Instance().startTime;
}

QByteArray ApiManager::ApiAnswer::GetData()
{
//<?xml version="1.0" encoding="UTF-8"?>
	QString tmp("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
	tmp.append("<api>");
	tmp.append(GetInternalData());
	tmp.append("</api>");
	return tmp.toUtf8();
}

ApiManager::ApiAnswer * ApiManager::ProcessApiCall(QString const& request, HTTPRequest & hRequest)
{
	//if(request.startsWith("/ojn/FR/api") || request.startsWith("/vl/FR/api"))
	QRegExp rx("(ojn|vl)/([A-Z]{2})/api");
	if(request.contains(rx) || request.contains("/ojn/FR/api") || request.startsWith("/vl/FR/api"))
	{
		QMetaObject::invokeMethod(statsPlugin, "AddApiCount");
		return ProcessBunnyVioletApiCall(request, hRequest);
	}
	else
	{
		Account const& account = hRequest.HasArg("token")?AccountManager::Instance().GetAccount(hRequest.GetArg("token").toLatin1()):AccountManager::Guest();
		hRequest.RemoveArg("token");

		if(request.startsWith("global/"))
			return ProcessGlobalApiCall(account, request.mid(7), hRequest);

		if(request.startsWith("plugins/"))
			return PluginManager::Instance().ProcessApiCall(account, request.mid(8), hRequest);

		if(request.startsWith("tts/"))
			return TTSManager::Instance().ProcessApiCall(account, request.mid(4), hRequest);

		if(request.startsWith("cron/"))
			return Cron::Instance().ProcessApiCall(account, request.mid(5), hRequest);

		if(request.startsWith("plugin/"))
			return ProcessPluginApiCall(account, request.mid(7), hRequest);

		if(request.startsWith("translate/"))
			return Translator::Instance().ProcessApiCall(account, request.mid(10), hRequest);

		if(request.startsWith("sentences/"))
			return SentenceManager::Instance().ProcessApiCall(account, request.mid(10), hRequest);

		if(request.startsWith("bunnies/"))
			return BunnyManager::Instance().ProcessApiCall(account, request.mid(8), hRequest);

		if(request.startsWith("bunny/"))
			return ProcessBunnyApiCall(account, request.mid(6), hRequest);

                if(request.startsWith("timezones/"))
                        return TimezoneManager::Instance().ProcessApiCall(account, request.mid(10), hRequest);

                if(request.startsWith("timezone/"))
                        return ProcessTimezoneApiCall(account, request.mid(9), hRequest);

		if(request.startsWith("ztamps/"))
			return ZtampManager::Instance().ProcessApiCall(account, request.mid(7), hRequest);

		if(request.startsWith("ztamp/"))
			return ProcessZtampApiCall(account, request.mid(6), hRequest);

		if(request.startsWith("accounts/"))
			return AccountManager::Instance().ProcessApiCall(account, request.mid(9), hRequest);

		return new ApiManager::ApiError(Translator::tr("Unknown Api Call : %1").arg(hRequest.toString()));
	}
}

ApiManager::ApiAnswer * ApiManager::ProcessGlobalApiCall(Account const& account, QString const& request, HTTPRequest const& hRequest)
{
	if(request == "about")
	{
		return new ApiManager::ApiString("OpenJabNab v0.99 - (Build " __DATE__ " / " __TIME__ ")");
	}
 	else if(request == "config")
 	{
		if(hRequest.HasArg("config"))
		{
			QString config = hRequest.GetArg("config");
			if(hRequest.HasArg("set"))
			{
				QString set = hRequest.GetArg("set");
 				return new ApiManager::ApiString(GlobalSettings::Set(config, set));
			}
			else
			{
 				return new ApiManager::ApiString(GlobalSettings::Get(config, QString()).toString());
			}
		}
		else
		{
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("config"));
		}
	}
 	else if(request == "uptime")
 	{
 		return new ApiManager::ApiString(QString::number(QDateTime::currentDateTime().toTime_t() - Instance().startTime));
	}
	else if(request == "ping")
	{
		return new ApiManager::ApiString(QString::number(BunnyManager::Instance().GetConnectedBunnyCount()) + "/" + QString::number(GlobalSettings::GetInt("Config/MaxNumberOfBunnies", 64)) + "/" + QString::number(GlobalSettings::GetInt("Config/MaxBurstNumberOfBunnies", GlobalSettings::GetInt("Config/MaxNumberOfBunnies", 64))));
	}
	else if (request == "system")
	{
		QString system = "<system></system>";
		return new ApiManager::ApiXml(system);
	}
	else if (request == "stats")
	{
		int bunnies = BunnyManager::Instance().GetBunnyCount();
		int connectedBunnies = BunnyManager::Instance().GetConnectedBunnyCount();
		int connectedV1 = BunnyManager::Instance().GetConnectedBunnyCount(1);
		int connectedV2 = BunnyManager::Instance().GetConnectedBunnyCount(2);
		int connectedV3 = BunnyManager::Instance().GetConnectedBunnyCount(3);

		int ztamps = ZtampManager::Instance().GetZtampCount();

		int plugins = PluginManager::Instance().GetPluginCount();
		int enabledPlugins = PluginManager::Instance().GetEnabledPluginCount();

		QString stats = "<bunnies>" + QString::number(bunnies) + "</bunnies>";
		stats += "<connected_bunnies>" + QString::number(connectedBunnies) + "</connected_bunnies>";
		stats += "<connected_v1>" + QString::number(connectedV1) + "</connected_v1>";
		stats += "<connected_v2>" + QString::number(connectedV2) + "</connected_v2>";
		stats += "<connected_v3>" + QString::number(connectedV3) + "</connected_v3>";
		stats += "<ztamps>" + QString::number(ztamps) + "</ztamps>";
		stats += "<plugins>" + QString::number(plugins) + "</plugins>";
		stats += "<enabled_plugins>" + QString::number(enabledPlugins) + "</enabled_plugins>";
		stats += "<uptime>" + QString::number(QDateTime::currentDateTime().toTime_t() - Instance().startTime) + "</uptime>";
		return new ApiManager::ApiXml(stats);
	}

	if(!account.HasAccess(Account::AcGlobal,Account::Read))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if (request == "getListOfApiCalls")
	{
		// Todo send a list with available api calls
	}
	return new ApiManager::ApiError(Translator::tr("Unknown Global Api Call : %1", account).arg(hRequest.toString()));
}

ApiManager::ApiAnswer * ApiManager::ProcessPluginApiCall(Account const& account, QString const& request, HTTPRequest & hRequest)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() != 2)
		return new ApiManager::ApiError(Translator::tr("Malformed Plugin Api Call : %1", account).arg(hRequest.toString()));

	QString const& pluginName = list.at(0);
	QString const& functionName = list.at(1);

	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(pluginName);
	if(!plugin)
		return new ApiManager::ApiError(Translator::tr("Unknown Plugin : %1<br />Request was : %2", account).arg(pluginName,hRequest.toString()));

	if(!plugin->GetEnable())
		return new ApiManager::ApiError(Translator::tr("This plugin is disabled", account));

	if(!functionName.contains("remove") && !hRequest.IsValid())
		return new ApiManager::ApiError(Translator::tr("Time format must be hh:mm", account));

	return plugin->ProcessApiCall(account, functionName, hRequest);
}

ApiManager::ApiAnswer * ApiManager::ProcessBunnyApiCall(Account const& account, QString const& request, HTTPRequest & hRequest)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() < 2)
		return new ApiManager::ApiError(Translator::tr("Malformed Bunny Api Call : %1", account).arg(hRequest.toString()));

	QByteArray const& bunnyID = list.at(0).toLatin1();

	if(!account.HasBunnyAccess(bunnyID))
		return new ApiManager::ApiError(Translator::tr("Access denied to this bunny", account));

	Bunny * b = BunnyManager::GetBunny(bunnyID);

	if(b != NULL)
	{
		if(list.size() == 2)
		{
			QByteArray const& functionName = list.at(1).toLatin1();
			if(!functionName.contains("remove") && !hRequest.IsValid())
				return new ApiManager::ApiError(Translator::tr("Time format must be hh:mm", account));

			return b->ProcessApiCall(account, functionName, hRequest);
		}
		else if(list.size() == 3)
		{
				PluginInterface * plugin = PluginManager::Instance().GetPluginByName(list.at(1).toLatin1());
				if(!plugin)
					return new ApiManager::ApiError(Translator::tr("Unknown Plugin : '%1'", account).arg(list.at(1)));

				if(b->HasPlugin(plugin) || ( (plugin->GetType() & PluginInterface::SystemPlugin || plugin->GetType() & PluginInterface::RequiredPlugin ) && plugin->GetEnable()))
				{
					QByteArray const& functionName = list.at(2).toLatin1();
					if(!functionName.contains("remove") && !hRequest.IsValid())
						return new ApiManager::ApiError(Translator::tr("Time format must be hh:mm", account));

					return plugin->ProcessBunnyApiCall(b, account, functionName, hRequest);
				}
			else
				return new ApiManager::ApiError(Translator::tr("This plugin is not enabled for this bunny", account));
		}
		else
			return new ApiManager::ApiError(Translator::tr("Malformed Plugin Api Call : %1", account).arg(hRequest.toString()));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Unknow bunny : %1").arg(QString(bunnyID)));
	}
}

ApiManager::ApiAnswer * ApiManager::ProcessTimezoneApiCall(Account const& account, QString const& request, HTTPRequest const& hRequest)
{
        QStringList list = QString(request).split('/', QString::SkipEmptyParts);

        if(list.size() != 3)
                return new ApiManager::ApiError(Translator::tr("Malformed Timezone Api Call : %1", account).arg(hRequest.toString()));

        QString const& area = list.at(0).toLatin1();
        QString const& location = list.at(1).toLatin1();
        Timezone * t = TimezoneManager::GetTimezone(area, location);
	if(!t)
		return new ApiManager::ApiError(Translator::tr("Unknown Timezone : %1/%2", account).arg(area, location));

        QByteArray const& functionName = list.at(2).toLatin1();
        return t->ProcessApiCall(account, functionName, hRequest);
}

ApiManager::ApiAnswer * ApiManager::ProcessBunnyVioletApiCall(QString const& request, HTTPRequest const& hRequest)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() < 3)
		return new ApiManager::ApiError(QString("Malformed Bunny Api Call : %1").arg(hRequest.toString()));

	QString serial = hRequest.GetArg("sn");

	Bunny * b = BunnyManager::GetKnownBunny(serial.toLatin1());

	if(b != NULL)
	{
		if(list.size() == 3)
		{
			return b->ProcessVioletApiCall(hRequest);
		}
		else
			return new ApiManager::ApiError(Translator::tr("Malformed Plugin Api Call : %1").arg(hRequest.toString()));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Unknow bunny : %1").arg(serial));
	}
}

ApiManager::ApiAnswer * ApiManager::ProcessZtampApiCall(Account const& account, QString const& request, HTTPRequest & hRequest)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() < 2)
		return new ApiManager::ApiError(Translator::tr("Malformed Ztamp Api Call : %1", account).arg(hRequest.toString()));

	QByteArray const& ztampID = list.at(0).toLatin1();

	if(!account.HasZtampAccess(ztampID))
		return new ApiManager::ApiError(Translator::tr("Access denied to this ztamp", account));

	Ztamp * z = ZtampManager::GetZtamp(ztampID);

	if(list.size() == 2)
	{
		QByteArray const& functionName = list.at(1).toLatin1();
		if(!functionName.contains("remove") && !hRequest.IsValid())
			return new ApiManager::ApiError(Translator::tr("Time format must be hh:mm", account));

		return z->ProcessApiCall(account, functionName, hRequest);
	}
	else if(list.size() == 3)
	{
			PluginInterface * plugin = PluginManager::Instance().GetPluginByName(list.at(1).toLatin1());
			if(!plugin)
				return new ApiManager::ApiError(Translator::tr("Unknown Plugin : '%1'", account).arg(list.at(1)));

			if(z->HasPlugin(plugin))
			{
				QByteArray const& functionName = list.at(2).toLatin1();
				if(!functionName.contains("remove") && !hRequest.IsValid())
					return new ApiManager::ApiError(Translator::tr("Time format must be hh:mm", account));

				return plugin->ProcessZtampApiCall(z, account, functionName, hRequest);
			}
		else
			return new ApiManager::ApiError(Translator::tr("This plugin is not enabled for this ztamp", account));
	}
	else
		return new ApiManager::ApiError(Translator::tr("Malformed Plugin Api Call : %1", account).arg(hRequest.toString()));
}

QString ApiManager::ApiAnswer::SanitizeXML(QString const& msg)
{
	if(msg.contains('<') || msg.contains('>') || msg.contains('&'))
		return "<![CDATA[" + msg + "]]>";
	return msg;
}

QString ApiManager::ApiError::GetInternalData()
{
	return QString("<error>%1</error>").arg(SanitizeXML(error));
}

QString ApiManager::ApiOk::GetInternalData()
{
	return QString("<ok>%1</ok>").arg(SanitizeXML(string));
}

QString ApiManager::ApiString::GetInternalData()
{
	return QString("<value>%1</value>").arg(SanitizeXML(string));
}

QString ApiManager::ApiList::GetInternalData()
{
	QString tmp;
	tmp += "<list>";
	foreach (QString b, list)
		tmp += QString("<item>%1</item>").arg(SanitizeXML(b));
	tmp += "</list>";
	return tmp;
}

QString ApiManager::ApiMappedList::GetInternalData()
{
	QString tmp;
	tmp += "<list>";
	QMapIterator<QString, QVariant> i(list);
	while (i.hasNext()) {
		i.next();
		tmp += QString("<item><key>%1</key><value>%2</value></item>").arg(SanitizeXML(i.key()), SanitizeXML(i.value().toString()));
	}
	tmp += "</list>";
	return tmp;
}

void ApiManager::ApiViolet::AddMessage(QString m, QString c)
{
	string += "<message>" + m + "</message>";
	string += "<comment>" + c + "</comment>";
}

void ApiManager::ApiViolet::AddEarPosition(int l, int r)
{
	string += "<message>POSITIONEAR</message>";
	string += "<leftposition>" + QString::number(l) + "</leftposition>";
	string += "<rightposition>" + QString::number(r) + "</rightposition>";
}

QByteArray ApiManager::ApiViolet::GetData()
{
//<?xml version="1.0" encoding="UTF-8"?>
	QString tmp("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
	tmp.append("<rsp>");
	tmp.append(GetInternalData());
	tmp.append("</rsp>");
	return tmp.toUtf8();
}

QByteArray ApiManager::ApiClear::GetData()
{
	return GetInternalData().toUtf8();
}
