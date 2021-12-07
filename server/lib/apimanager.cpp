#include <QUrl>
#include <QStringList>

#include "account.h"
#include "accountmanager.h"
#include "apimanager.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "httprequest.h"
#include "plugininterface.h"
#include "pluginmanager.h"
#include "sentencemanager.h"
#include "translator.h"
#include "tts/ttsmanager.h"
#include "version.h"

PluginInterface * statsPlugin;

ApiManager::ApiManager()
{
	startTime = QDateTime::currentDateTime().toTime_t();
	statsPlugin = PluginManager::Instance().GetPluginByName("stats");
}

void ApiManager::InitApiCalls(void)
{
	DECLARE_API_CALL("about()",         &ApiManager::Api_About);
	DECLARE_API_CALL("config(config)",  &ApiManager::Api_Config);
	DECLARE_API_CALL("ping()",          &ApiManager::Api_Ping);
	DECLARE_API_CALL("stats()",         &ApiManager::Api_Stats);
	DECLARE_API_CALL("system()",        &ApiManager::Api_System);
	DECLARE_API_CALL("uptime()",        &ApiManager::Api_Uptime);
}

API_CALL(ApiManager::Api_About)
{
	QMap<QString,QVariant> ret;
	ret["name"] = "OpenJabNab";
	ret["version"] = __version;
	ret["git_rev"] = __git_rev;
	ret["build_date"] = __build_date;
	ret["build_time"] = __build_time;
	return new ApiAnswers::MappedList(ret);
}

API_CALL(ApiManager::Api_Config)
{
	if(!account.HasAccess(Account::AcGlobal,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(hRequest.HasArg("config"))
	{
		QString config = hRequest.GetArg("config");
		if(hRequest.HasArg("set"))
		{
			QString set = hRequest.GetArg("set");
			return new ApiAnswers::String(GlobalSettings::Set(config, set));
		}
		else
		{
			return new ApiAnswers::String(GlobalSettings::Get(config, QString()).toString());
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("config"));
	}
}

API_CALL(ApiManager::Api_Ping)
{
	return new ApiAnswers::String(QString::number(BunnyManager::Instance().GetConnectedBunnyCount()) + "/" + QString::number(GlobalSettings::GetInt("Config/MaxNumberOfBunnies", 64)) + "/" + QString::number(GlobalSettings::GetInt("Config/MaxBurstNumberOfBunnies", GlobalSettings::GetInt("Config/MaxNumberOfBunnies", 64))));
}

API_CALL(ApiManager::Api_Stats)
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
	return new ApiAnswers::Xml(stats);
}

API_CALL(ApiManager::Api_System)
{
	QString system = "<system></system>";
	return new ApiAnswers::Xml(system);
}

API_CALL(ApiManager::Api_Uptime)
{
	return new ApiAnswers::String(QString::number(QDateTime::currentDateTime().toTime_t() - Instance().startTime));
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

QByteArray ApiAnswers::Answer::GetData()
{
//<?xml version="1.0" encoding="UTF-8"?>
	QString tmp("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
	tmp.append("<api>");
	tmp.append(GetInternalData());
	tmp.append("</api>");
	return tmp.toUtf8();
}

ApiAnswers::Answer* ApiManager::ProcessApiCall(QString const& request, HTTPRequest & hRequest)
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
			return ApiHandler<ApiManager>::ProcessApiCall(request.mid(7), hRequest, account);

		if(request.startsWith("plugins/"))
			return PluginManager::Instance().ProcessApiCall(request.mid(8), hRequest, account);

		if(request.startsWith("tts/"))
			return TTSManager::Instance().ProcessApiCall(request.mid(4), hRequest, account);

		if(request.startsWith("cron/"))
			return Cron::Instance().ProcessApiCall(request.mid(5), hRequest, account);

		if(request.startsWith("plugin/"))
			return ProcessPluginApiCall(request.mid(7), hRequest, account);

		if(request.startsWith("translate/"))
			return Translator::Instance().ProcessApiCall(request.mid(10), hRequest, account);

		if(request.startsWith("sentences/"))
			return SentenceManager::Instance().ProcessApiCall(request.mid(10), hRequest, account);

		if(request.startsWith("bunnies/"))
			return BunnyManager::Instance().ProcessApiCall(request.mid(8), hRequest, account);

		if(request.startsWith("bunny/"))
			return ProcessBunnyApiCall(request.mid(6), hRequest, account);

		if(request.startsWith("ztamps/"))
			return ZtampManager::Instance().ProcessApiCall(request.mid(7), hRequest, account);

		if(request.startsWith("ztamp/"))
			return ProcessZtampApiCall(request.mid(6), hRequest, account);

		if(request.startsWith("accounts/"))
			return AccountManager::Instance().ProcessApiCall(request.mid(9), hRequest, account);

		return new ApiAnswers::Error(Translator::tr("Unknown Api Call : %1").arg(hRequest.toString()));
	}
}

ApiAnswers::Answer * ApiManager::ProcessPluginApiCall(QString const& request, HTTPRequest const& hRequest, Account const& account)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() != 2)
		return new ApiAnswers::Error(Translator::tr("Malformed Plugin Api Call : %1", account).arg(hRequest.toString()));

	QString const& pluginName = list.at(0);
	QString const& functionName = list.at(1);

	PluginInterface * plugin = PluginManager::Instance().GetPluginByName(pluginName);
	if(!plugin)
		return new ApiAnswers::Error(Translator::tr("Unknown Plugin : %1<br />Request was : %2", account).arg(pluginName,hRequest.toString()));

	if(!plugin->GetEnable())
		return new ApiAnswers::Error(Translator::tr("This plugin is disabled", account));

	return plugin->ProcessApiCall(functionName, hRequest, account);
}

ApiAnswers::Answer * ApiManager::ProcessBunnyApiCall(QString const& request, HTTPRequest const& hRequest, Account const& account)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() < 2)
		return new ApiAnswers::Error(Translator::tr("Malformed Bunny Api Call : %1", account).arg(hRequest.toString()));

	QByteArray const& bunnyID = list.at(0).toLatin1();

	if(!account.HasBunnyAccess(bunnyID))
		return new ApiAnswers::Error(Translator::tr("Access denied to this bunny", account));

	Bunny * b = BunnyManager::GetBunny(bunnyID);

	if(b != NULL)
	{
		if(list.size() == 2)
		{
			QByteArray const& functionName = list.at(1).toLatin1();
			return b->ProcessApiCall(functionName, hRequest, account);
		}
		else if(list.size() == 3)
		{
				PluginInterface * plugin = PluginManager::Instance().GetPluginByName(list.at(1).toLatin1());
				if(!plugin)
					return new ApiAnswers::Error(Translator::tr("Unknown Plugin : '%1'", account).arg(list.at(1)));

				if(b->HasPlugin(plugin) || ( (plugin->GetType() & PluginInterface::SystemPlugin || plugin->GetType() & PluginInterface::RequiredPlugin ) && plugin->GetEnable()))
				{
					QByteArray const& functionName = list.at(2).toLatin1();

					return plugin->ProcessBunnyApiCall(functionName, hRequest, account, b);
				}
			else
				return new ApiAnswers::Error(Translator::tr("This plugin is not enabled for this bunny", account));
		}
		else
			return new ApiAnswers::Error(Translator::tr("Malformed Plugin Api Call : %1", account).arg(hRequest.toString()));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Unknow bunny : %1").arg(QString(bunnyID)));
	}
}

ApiAnswers::Answer * ApiManager::ProcessBunnyVioletApiCall(QString const& request, HTTPRequest const& hRequest)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() < 3)
		return new ApiAnswers::Error(QString("Malformed Bunny Api Call : %1").arg(hRequest.toString()));

	QString serial = hRequest.GetArg("sn");

	Bunny * b = BunnyManager::GetKnownBunny(serial.toLatin1());

	if(b != NULL)
	{
		if(list.size() == 3)
		{
			return b->ProcessVioletApiCall(hRequest);
		}
		else
			return new ApiAnswers::Error(Translator::tr("Malformed Plugin Api Call : %1").arg(hRequest.toString()));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Unknow bunny : %1").arg(serial));
	}
}

ApiAnswers::Answer * ApiManager::ProcessZtampApiCall(QString const& request, HTTPRequest const& hRequest, Account const& account)
{
	QStringList list = QString(request).split('/', QString::SkipEmptyParts);

	if(list.size() < 2)
		return new ApiAnswers::Error(Translator::tr("Malformed Ztamp Api Call : %1", account).arg(hRequest.toString()));

	QByteArray const& ztampID = list.at(0).toLatin1();

	if(!account.HasZtampAccess(ztampID))
		return new ApiAnswers::Error(Translator::tr("Access denied to this ztamp", account));

	Ztamp * z = ZtampManager::GetZtamp(ztampID);

	if(list.size() == 2)
	{
		QByteArray const& functionName = list.at(1).toLatin1();

		return z->ProcessApiCall(functionName, hRequest, account);
	}
	else if(list.size() == 3)
	{
			PluginInterface * plugin = PluginManager::Instance().GetPluginByName(list.at(1).toLatin1());
			if(!plugin)
				return new ApiAnswers::Error(Translator::tr("Unknown Plugin : '%1'", account).arg(list.at(1)));

			if(z->HasPlugin(plugin))
			{
				QByteArray const& functionName = list.at(2).toLatin1();

				return plugin->ProcessZtampApiCall(functionName, hRequest, account, z);
			}
		else
			return new ApiAnswers::Error(Translator::tr("This plugin is not enabled for this ztamp", account));
	}
	else
		return new ApiAnswers::Error(Translator::tr("Malformed Plugin Api Call : %1", account).arg(hRequest.toString()));
}

QString ApiAnswers::Answer::SanitizeXML(QString const& msg)
{
	if(msg.contains('<') || msg.contains('>') || msg.contains('&'))
		return "<![CDATA[" + msg + "]]>";
	return msg;
}

QString ApiAnswers::Error::GetInternalData()
{
	return QString("<error>%1</error>").arg(SanitizeXML(error));
}

QString ApiAnswers::Ok::GetInternalData()
{
	return QString("<ok>%1</ok>").arg(SanitizeXML(string));
}

QString ApiAnswers::String::GetInternalData()
{
	return QString("<value>%1</value>").arg(SanitizeXML(string));
}

QString ApiAnswers::List::GetInternalData()
{
	QString tmp;
	tmp += "<list>";
	foreach (QString b, list)
		tmp += QString("<item>%1</item>").arg(SanitizeXML(b));
	tmp += "</list>";
	return tmp;
}

QString ApiAnswers::MappedList::GetInternalData()
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

void ApiAnswers::Violet::AddMessage(QString m, QString c)
{
	string += "<message>" + m + "</message>";
	string += "<comment>" + c + "</comment>";
}

void ApiAnswers::Violet::AddEarPosition(int l, int r)
{
	string += "<message>POSITIONEAR</message>";
	string += "<leftposition>" + QString::number(l) + "</leftposition>";
	string += "<rightposition>" + QString::number(r) + "</rightposition>";
}

QByteArray ApiAnswers::Violet::GetData()
{
//<?xml version="1.0" encoding="UTF-8"?>
	QString tmp("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
	tmp.append("<rsp>");
	tmp.append(GetInternalData());
	tmp.append("</rsp>");
	return tmp.toUtf8();
}

QByteArray ApiAnswers::Clear::GetData()
{
	return GetInternalData().toUtf8();
}
