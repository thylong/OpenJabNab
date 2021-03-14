#include <QCoreApplication>
#include <QDir>
#include <QLibrary>
#include <QPluginLoader>
#include <QString>

#include "account.h"
#include "httprequest.h"
#include "log.h"
#include "pluginmanager.h"
#include "translator.h"
#include <iostream>

PluginManager::PluginManager()
{
	// Load all plugins
  pluginsDir = QDir(GlobalSettings::GetString("Directories/PluginLibsDir",
                    QCoreApplication::applicationDirPath().append("/lib/plugins/")
  )                );

  QString pcPath = GlobalSettings::GetConfigDir().append("/plugins/");
  QDir pluginConfDir = QDir(pcPath);
  if(!pluginConfDir.exists())
  {
    if(!pluginConfDir.mkpath(pcPath))
    {
      LogError("Unable to create plugins config directory !\n");
			exit(-1);
    }
  }
	obsoleteList = GlobalSettings::Get("Plugins/Obsolete", QStringList()).toStringList();
}

PluginManager & PluginManager::Instance()
{
  static PluginManager p;
  return p;
}

void PluginManager::UnloadPlugins()
{
	foreach(PluginInterface * p, listOfPlugins)
		delete p;

	foreach(QPluginLoader * l, listOfPluginsLoader.values())
	{
		l->unload();
		delete l;
	}

}

int PluginManager::GetEnabledPluginCount()
{
	int active = 0;
	foreach(PluginInterface * plugin, listOfPlugins)
		if(plugin->GetEnable())
			active++;
	return active;
}

int PluginManager::GetPluginCount()
{
	return listOfPlugins.count();
}

void PluginManager::LoadPlugins()
{
	LogInfo(QString("Finding plugins in : %1").arg(pluginsDir.path()));
	foreach (QString fileName, pluginsDir.entryList(QDir::Files))
		LoadPlugin(fileName);
}

bool PluginManager::LoadPlugin(QString const& fileName)
{
	if(listOfPluginsByFileName.contains(fileName))
	{
		LogError(QString("Plugin '%1' already loaded !").arg(fileName));
		return false;
	}

	QString file = pluginsDir.absoluteFilePath(fileName);
	if (!QLibrary::isLibrary(file))
		return false;

	QString status = QString("Loading %1 : ").arg(fileName);

	QPluginLoader * loader = new QPluginLoader(file);
  loader->setLoadHints(QLibrary::LoadHints()); // Clear QLibrary::PreventUnloadHint, allowing library file reload !
	QObject * p = loader->instance();
	PluginInterface * plugin = qobject_cast<PluginInterface *>(p);
	if (plugin)
	{
		if(plugin->Init() == false)
		{
			status.append(QString("%1 OK, Initialisation failed").arg(plugin->GetName()));
			LogInfo(status);
			delete plugin;
			loader->unload();
			delete loader;

			return false;
		}

		listOfPlugins.append(plugin);
		listOfPluginsFileName.insert(plugin, fileName);
		listOfPluginsLoader.insert(plugin, loader);
		listOfPluginsByName.insert(plugin->GetName(), plugin);
		listOfPluginsByFileName.insert(fileName, plugin);
		if(!(plugin->GetType() & PluginInterface::BunnyV1Plugin) && !(plugin->GetType() & PluginInterface::BunnyV2Plugin) && !(plugin->GetType() & PluginInterface::ZtampPlugin) )
			listOfSystemPlugins.append(plugin);
		else
			BunnyManager::PluginLoaded(plugin);

		// Init Api Calls
		plugin->InitApiCalls();

		status.append(QString("%1 OK, Enable : %2").arg(plugin->GetName(),plugin->GetEnable() ? "Yes" : "No"));
		LogInfo(status);
		return true;
	}
	status.append("Failed, ").append(loader->errorString());
	LogInfo(status);
	return false;
}

bool PluginManager::UnloadPlugin(QString const& name)
{
	if(listOfPluginsByName.contains(name))
	{
		PluginInterface * p = listOfPluginsByName.value(name);
		if(p->GetType() & PluginInterface::BunnyV1Plugin || p->GetType() & PluginInterface::BunnyV2Plugin)
			BunnyManager::PluginUnloaded(p);
		if(p->GetType() & PluginInterface::ZtampPlugin)
			ZtampManager::PluginUnloaded(p);
		QString fileName = listOfPluginsFileName.value(p);
		QPluginLoader * loader = listOfPluginsLoader.value(p);
		listOfPluginsByFileName.remove(fileName);
		listOfPluginsFileName.remove(p);
		listOfPluginsLoader.remove(p);
		listOfPluginsByName.remove(name);
		listOfPlugins.removeAll(p);
		listOfSystemPlugins.removeAll(p);
		delete p;
		loader->unload();
		delete loader;
		LogInfo(QString("Plugin %1 unloaded.").arg(name));
		return true;
	}
	LogInfo(QString("Can't unload plugin %1").arg(name));
	return false;
}

bool PluginManager::ReloadPlugin(QString const& name)
{
	if(listOfPluginsByName.contains(name))
	{
		PluginInterface * p = listOfPluginsByName.value(name);
		QString file = listOfPluginsFileName.value(p);
		return (UnloadPlugin(name) && LoadPlugin(file));
	}
	return false;
}

/**************************************************/
/* HTTP requests are sent to ALL 'active' plugins */
/**************************************************/
void PluginManager::HttpRequestBefore(HTTPRequest & request)
{
	// Call RequestBefore for all plugins
	foreach(PluginInterface * plugin, listOfPlugins)
		if(plugin->GetEnable())
			plugin->HttpRequestBefore(request);
}

bool PluginManager::HttpRequestHandle(HTTPRequest & request)
{
	// Call GetAnswer for all plugins until one returns true
	foreach(PluginInterface * plugin, listOfPlugins)
	{
		if(plugin->GetEnable() && plugin->HttpRequestHandle(request))
			return true;
	}
	return false;
}

void PluginManager::HttpRequestAfter(HTTPRequest & request)
{
	// Call RequestAfter for all plugins
	foreach(PluginInterface * plugin, listOfPlugins)
		if(plugin->GetEnable())
			plugin->HttpRequestAfter(request);
}

/*****************************************************/
/* Others requests are sent only to "system" plugins */
/*****************************************************/
// Bunny -> OJN Message
bool PluginManager::XmppBunnyMessage(Bunny * b, QByteArray const& data)
{
	bool handled = false;
	foreach(PluginInterface * plugin, listOfSystemPlugins)
		if(plugin->GetEnable())
			handled |= plugin->XmppBunnyMessage(b, data);
	return handled;
}

void PluginManager::BeforeSendMessage(Bunny * b, MessagePacket * p, QString sender)
{
	foreach(PluginInterface * plugin, listOfSystemPlugins)
		if(plugin->GetEnable())
			plugin->BeforeSendMessage(b, p, sender);
}

void PluginManager::OnInitPacket(const Bunny *b, AmbientPacket &a, SleepPacket &s)
{
	foreach(PluginInterface * plugin, listOfSystemPlugins)
		if(plugin->GetEnable())
			plugin->OnInitPacket(b, a, s);

}

// Bunny OnClick
bool PluginManager::OnClick(Bunny * b, PluginInterface::ClickType type)
{
	// Call OnClick for all 'system' plugins until one returns true
	foreach(PluginInterface * plugin, listOfSystemPlugins)
	{
		if(plugin->GetEnable())
		{
			if(plugin->OnClick(b, type))
				return true;
		}
	}
	return false;
}

bool PluginManager::OnEarsMove(Bunny * b, int left, int right)
{
	// Call OnEarsMove for all 'system' plugins until one returns true
	foreach(PluginInterface * plugin, listOfSystemPlugins)
	{
		if(plugin->GetEnable())
		{
			if(plugin->OnEarsMove(b, left, right))
				return true;
		}
	}
	return false;
}

bool PluginManager::OnListen(Bunny * b, int volume)
{
	// Call OnListen for all 'system' plugins until one returns true
	foreach(PluginInterface * plugin, listOfSystemPlugins)
	{
		if(plugin->GetEnable())
		{
			if(plugin->OnListen(b, volume))
				return true;
		}
	}
	return false;
}

bool PluginManager::OnVoiceCommand(Bunny * b, QString const& s, QStringList const& l)
{
	// Call OnRFID for all 'system' plugins until one returns true
	foreach(PluginInterface * plugin, listOfSystemPlugins)
	{
		if(plugin->GetEnable())
		{
			if(plugin->OnVoiceCommand(b, s, l))
				return true;
		}
	}
	return false;
}

bool PluginManager::OnRFID(Ztamp * z, Bunny * b)
{
	// Call OnRFID for all 'system' plugins until one returns true
	foreach(PluginInterface * plugin, listOfSystemPlugins)
	{
		if(plugin->GetEnable())
		{
			if(plugin->OnRFID(z, b))
				return true;
		}
	}
	return false;
}

bool PluginManager::OnRFID(Bunny * b, QByteArray const& id)
{
	// Call OnRFID for all 'system' plugins until one returns true
	foreach(PluginInterface * plugin, listOfSystemPlugins)
	{
		if(plugin->GetEnable())
		{
			if(plugin->OnRFID(b, id))
				return true;
		}
	}
	return false;
}

bool PluginManager::OnRecord(Bunny * b, QString const& filename, bool before)
{
	// Call OnRecord for all 'system' plugins until one returns true
	foreach(PluginInterface * plugin, listOfSystemPlugins)
	{
		if(plugin->GetEnable())
		{
			if(
				( before && !(plugin->GetType() & PluginInterface::SystemAfterPlugin) )
				|| ( !before && (plugin->GetType() & PluginInterface::SystemAfterPlugin) )
			)
			{
				if(plugin->OnRecord(b, filename))
					return true;
			}
		}
	}
	return false;
}

bool PluginManager::OnRecord(Bunny * b, QString const& filename)
{
	return OnRecord(b, filename, true);
}

// Bunny Connect
void PluginManager::OnBunnyConnect(Bunny * b)
{
	foreach(PluginInterface * plugin, listOfSystemPlugins)
		if(plugin->GetEnable())
			plugin->OnBunnyConnect(b);
}

// Bunny Connect
void PluginManager::OnBunnyDisconnect(Bunny * b)
{
	foreach(PluginInterface * plugin, listOfSystemPlugins)
		if(plugin->GetEnable())
			plugin->OnBunnyDisconnect(b);
}

// Ztamp Connect
void PluginManager::OnZtampConnect(Ztamp * b)
{
	foreach(PluginInterface * plugin, listOfSystemPlugins)
		if(plugin->GetEnable())
			plugin->OnZtampConnect(b);
}

// Ztamp Disconnect
void PluginManager::OnZtampDisconnect(Ztamp * b)
{
	foreach(PluginInterface * plugin, listOfSystemPlugins)
		if(plugin->GetEnable())
			plugin->OnZtampDisconnect(b);
}

/*******
 * API *
 *******/

void PluginManager::InitApiCalls()
{
	DECLARE_API_CALL("getPluginsApiCall()", &PluginManager::Api_GetPluginsVioletApiCall);

	DECLARE_API_CALL("config()", &PluginManager::Api_Plugins);

	DECLARE_API_CALL("getPlugins()", &PluginManager::Api_GetPlugins);
	DECLARE_API_CALL("getPlugin()", &PluginManager::Api_GetPlugin);

	DECLARE_API_CALL("getListOfPlugins()", &PluginManager::Api_GetListOfPlugins);
	DECLARE_API_CALL("getListOfEnabledPlugins()", &PluginManager::Api_GetListOfEnabledPlugins);
	DECLARE_API_CALL("getListOfTTSLogPlugins()", &PluginManager::Api_GetListOfTTSLogPlugins);

	DECLARE_API_CALL("getListOfBunnyPlugins()", &PluginManager::Api_GetListOfBunnyV2Plugins);
	DECLARE_API_CALL("getListOfBunnyV1Plugins()", &PluginManager::Api_GetListOfBunnyV1Plugins);
	DECLARE_API_CALL("getListOfBunnyEnabledPlugins()", &PluginManager::Api_GetListOfBunnyEnabledPlugins);

	DECLARE_API_CALL("getListOfZtampPlugins()", &PluginManager::Api_GetListOfZtampPlugins);
	DECLARE_API_CALL("getListOfZtampEnabledPlugins()", &PluginManager::Api_GetListOfZtampEnabledPlugins);

	DECLARE_API_CALL("getListOfRequiredPlugins()", &PluginManager::Api_GetListOfRequiredPlugins);
	DECLARE_API_CALL("getListOfSystemPlugins()", &PluginManager::Api_GetListOfSystemPlugins);
	DECLARE_API_CALL("getListOfSystemEnabledPlugins()", &PluginManager::Api_GetListOfSystemEnabledPlugins);

	DECLARE_API_CALL("activatePlugin(name)", &PluginManager::Api_ActivatePlugin);
	DECLARE_API_CALL("deactivatePlugin(name)", &PluginManager::Api_DeactivatePlugin);
	DECLARE_API_CALL("loadPlugin(filename)", &PluginManager::Api_LoadPlugin);
	DECLARE_API_CALL("unloadPlugin(name)", &PluginManager::Api_UnloadPlugin);
	DECLARE_API_CALL("reloadPlugin(name)", &PluginManager::Api_ReloadPlugin);

	DECLARE_API_CALL("ttslog()", &PluginManager::Api_TTSLogPlugin);
}

API_CALL(PluginManager::Api_GetPluginsVioletApiCall)
{
	//Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QString plugins = "<plugins>";
	foreach (PluginInterface * p, listOfPlugins)
	{
		if(p->GetType() & PluginInterface::ApiPlugin)
		{
			plugins += "<plugin ";
			plugins += "id='" + p->GetName() + "'>";
			QHashIterator<QString, QString> i(p->GetExtendedApiFunctions());
			while (i.hasNext()) {
				i.next();
				plugins += "<function type='" + i.value() + "'>" + i.key() + "</function>";
			}
			QHashIterator<QString, QString> j(p->GetVoiceCommands(account.GetLanguage()));
			while (j.hasNext()) {
				j.next();
				plugins += "<command description='" + j.value() + "'>" + j.key() + "</command>";
			}
			plugins += "</plugin>";
		}
	}
	plugins += "</plugins>";
	return new ApiAnswers::Xml(plugins);
}

API_CALL(PluginManager::Api_Plugins)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	/*
	if(!hRequest.HasArg("options"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("options"));

	QStringList options = hRequest.GetArg("options").split(",");
	*/

	if(action == "obsolete")
	{
		if(!hRequest.HasArg("subaction"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("subaction"));

		QString subaction = hRequest.GetArg("subaction");
		if(subaction == "list")
		{
			return new ApiAnswers::List(obsoleteList);
		}
		else if(subaction == "add")
		{
			if(!hRequest.HasArg("value"))
				return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("value"));

			QString value = hRequest.GetArg("value");

			if(!obsoleteList.contains(value))
			{
				obsoleteList.append(value);
				obsoleteList.removeDuplicates();
				GlobalSettings::Set("Plugins/Obsolete", obsoleteList);
				return new ApiAnswers::Ok(Translator::tr("Add plugin '%1' to obsolete list", account).arg(value));
			}
			return new ApiAnswers::Ok(Translator::tr("Plugin '%1' is already in obsolete list", account).arg(value));
		}
		else if(subaction == "remove")
		{
			if(!hRequest.HasArg("value"))
				return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("value"));

			QString value = hRequest.GetArg("value");

			if(obsoleteList.contains(value))
			{
				obsoleteList.removeAll(value);
				obsoleteList.removeDuplicates();
				GlobalSettings::Set("Plugins/Obsolete", obsoleteList);
				return new ApiAnswers::Ok(Translator::tr("Remove plugin '%1' from obsoloete list", account).arg(value));
			}
			return new ApiAnswers::Ok(Translator::tr("Plugin '%1' is not in obsolete list", account).arg(value));
		}
		else
		{
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("subaction"));
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(PluginManager::Api_GetPlugins)
{
	QString plugins = "<plugins>";
	QString lng = account.GetLanguage();
	if(hRequest.HasArg("lng"))
		lng = hRequest.GetArg("lng");
	foreach (PluginInterface * p, listOfPlugins)
	{
		plugins += "<plugin ";
		plugins += "id='" + p->GetName() + "' ";
		plugins += "version='" + p->GetVersion() + "' ";
		plugins += QString("enabled='%1' ").arg(p->GetEnable() ? "1" : "0" );
		plugins += QString("system='%1' ").arg(p->GetType() & PluginInterface::SystemPlugin ? "1" : "0" );
		plugins += QString("required='%1' ").arg(p->GetType() & PluginInterface::RequiredPlugin ? "1" : "0" );
		plugins += QString("v1='%1' ").arg(p->GetType() & PluginInterface::BunnyV1Plugin ? "1" : "0" );
		plugins += QString("v2='%1' ").arg(p->GetType() & PluginInterface::BunnyV2Plugin ? "1" : "0" );
		plugins += QString("rfid='%1' ").arg(p->GetType() & PluginInterface::RfidPlugin ? "1" : "0" );
		plugins += QString("ztamp='%1' ").arg(p->GetType() & PluginInterface::ZtampPlugin ? "1" : "0" );
		plugins += QString("single='%1' ").arg(p->GetType() & PluginInterface::SingleClickPlugin ? "1" : "0" );
		plugins += QString("double='%1' ").arg(p->GetType() & PluginInterface::DoubleClickPlugin ? "1" : "0" );
		plugins += QString("ears='%1' ").arg(p->GetType() & PluginInterface::EarsPlugin ? "1" : "0" );
		plugins += QString("listen='%1' ").arg(p->GetType() & PluginInterface::ListenPlugin ? "1" : "0" );
		plugins += QString("api='%1' ").arg(p->GetType() & PluginInterface::ApiPlugin ? "1" : "0" );
		plugins += QString("voice='%1' ").arg(p->GetType() & PluginInterface::VoicePlugin ? "1" : "0" );
		plugins += QString("record='%1' ").arg(p->GetType() & PluginInterface::RecordPlugin ? "1" : "0" );
		plugins += QString("cron='%1' ").arg(p->GetType() & PluginInterface::CronPlugin ? "1" : "0" );
		plugins += QString("period='%1' ").arg(p->GetType() & PluginInterface::PeriodPlugin ? "1" : "0" );
		plugins += QString("message='%1' ").arg(p->GetType() & PluginInterface::MessagePlugin ? "1" : "0" );
		plugins += QString("premium='%1' ").arg(p->GetType() & PluginInterface::PremiumPlugin ? "1" : "0" );
		plugins += QString("dev='%1' ").arg(p->GetType() & PluginInterface::DevPlugin ? "1" : "0" );
		plugins += QString("boot='%1' ").arg(QString::number(p->GetBootcodeRequirement()) );
		plugins += QString("ttslog='%1' ").arg(p->GetTTSLog() ? "1" : "0" );
		plugins += "languages='" + p->GetLanguages().join(",") + "' ";
		plugins += ">" + Translator::tr(p->GetVisualName(), lng) + "</plugin>";
	}
	plugins += "</plugins>";
	return new ApiAnswers::Xml(plugins);
}

API_CALL(PluginManager::Api_GetPlugin)
{
	if(!hRequest.HasArg("plugin"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("plugin"));

	QString plugin = hRequest.GetArg("plugin");

	QString plugins = "<plugins>";
	PluginInterface * p = GetPluginByName(plugin);
	if(p != NULL)
	{
		QString lng = account.GetLanguage();
		if(hRequest.HasArg("lng"))
			lng = hRequest.GetArg("lng");

		plugins += "<plugin ";
		plugins += "id='" + p->GetName() + "' ";
		plugins += "version='" + p->GetVersion() + "' ";
		plugins += QString("enabled='%1' ").arg(p->GetEnable() ? "1" : "0" );
		plugins += QString("system='%1' ").arg(p->GetType() & PluginInterface::SystemPlugin ? "1" : "0" );
		plugins += QString("required='%1' ").arg(p->GetType() & PluginInterface::RequiredPlugin ? "1" : "0" );
		plugins += QString("v1='%1' ").arg(p->GetType() & PluginInterface::BunnyV1Plugin ? "1" : "0" );
		plugins += QString("v2='%1' ").arg(p->GetType() & PluginInterface::BunnyV2Plugin ? "1" : "0" );
		plugins += QString("rfid='%1' ").arg(p->GetType() & PluginInterface::RfidPlugin ? "1" : "0" );
		plugins += QString("ztamp='%1' ").arg(p->GetType() & PluginInterface::ZtampPlugin ? "1" : "0" );
		plugins += QString("single='%1' ").arg(p->GetType() & PluginInterface::SingleClickPlugin ? "1" : "0" );
		plugins += QString("double='%1' ").arg(p->GetType() & PluginInterface::DoubleClickPlugin ? "1" : "0" );
		plugins += QString("ears='%1' ").arg(p->GetType() & PluginInterface::EarsPlugin ? "1" : "0" );
		plugins += QString("listen='%1' ").arg(p->GetType() & PluginInterface::ListenPlugin ? "1" : "0" );
		plugins += QString("api='%1' ").arg(p->GetType() & PluginInterface::ApiPlugin ? "1" : "0" );
		plugins += QString("voice='%1' ").arg(p->GetType() & PluginInterface::VoicePlugin ? "1" : "0" );
		plugins += QString("record='%1' ").arg(p->GetType() & PluginInterface::RecordPlugin ? "1" : "0" );
		plugins += QString("cron='%1' ").arg(p->GetType() & PluginInterface::CronPlugin ? "1" : "0" );
		plugins += QString("period='%1' ").arg(p->GetType() & PluginInterface::PeriodPlugin ? "1" : "0" );
		plugins += QString("message='%1' ").arg(p->GetType() & PluginInterface::MessagePlugin ? "1" : "0" );
		plugins += QString("premium='%1' ").arg(p->GetType() & PluginInterface::PremiumPlugin ? "1" : "0" );
		plugins += QString("dev='%1' ").arg(p->GetType() & PluginInterface::DevPlugin ? "1" : "0" );
		plugins += QString("boot='%1' ").arg(QString::number(p->GetBootcodeRequirement()) );
		plugins += QString("ttslog='%1' ").arg(p->GetTTSLog() ? "1" : "0" );
		plugins += "languages='" + p->GetLanguages().join(",") + "' ";
		plugins += ">" + Translator::tr(p->GetVisualName(), lng);
		plugins += "<changelog>";
		QHashIterator<QString, QString> i(p->GetChangelog());
		while (i.hasNext())
		{
			i.next();
			plugins += "<change version=\"" + i.key() + "\">" + i.value() + "</change>";
		}
		plugins += "</changelog></plugin>";
	}
	plugins += "</plugins>";
	return new ApiAnswers::Xml(plugins);
}

API_CALL(PluginManager::Api_GetListOfPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPlugins,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach (PluginInterface * p, listOfPlugins)
		list.insert(p->GetName(), Translator::tr(p->GetVisualName(), account));

	return new ApiAnswers::MappedList(list);
}

API_CALL(PluginManager::Api_GetListOfEnabledPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPlugins,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if(p->GetEnable())
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfTTSLogPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPlugins,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if(p->GetTTSLog())
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfBunnyV1Plugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPluginsBunny,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if(p->GetType() & PluginInterface::BunnyV1Plugin)
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}
API_CALL(PluginManager::Api_GetListOfBunnyV2Plugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPluginsBunny,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if(p->GetType() & PluginInterface::BunnyV2Plugin)
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfZtampPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPluginsZtamp,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if(p->GetType() & PluginInterface::ZtampPlugin)
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfSystemPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfSystemPlugins)
		if(p->GetType() & PluginInterface::SystemPlugin)
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfSystemEnabledPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfSystemPlugins)
		if(p->GetType() & PluginInterface::SystemPlugin && p->GetEnable())
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfRequiredPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if(p->GetType() & PluginInterface::RequiredPlugin)
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfBunnyEnabledPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPluginsBunny,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if((p->GetType() & PluginInterface::BunnyV1Plugin || p->GetType() & PluginInterface::BunnyV2Plugin) && p->GetEnable())
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_GetListOfZtampEnabledPlugins)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcPluginsZtamp,Account::Read))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (PluginInterface * p, listOfPlugins)
		if((p->GetType() & PluginInterface::ZtampPlugin) && p->GetEnable())
			list.append(p->GetName());

	return new ApiAnswers::List(list);
}

API_CALL(PluginManager::Api_ActivatePlugin)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Write))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	PluginInterface * p = listOfPluginsByName.value(hRequest.GetArg("name"));
	if(!p)
		return new ApiAnswers::Error(Translator::tr("Unknown plugin '%1'", account).arg(hRequest.GetArg("name")));

	if(p->GetEnable())
		return new ApiAnswers::Error(Translator::tr("Plugin '%1' is already enabled!", account).arg(hRequest.GetArg("name")));

	p->SetEnable(true);
	return new ApiAnswers::Ok(Translator::tr("'%1' is now enabled", account).arg(p->GetName()));
}

API_CALL(PluginManager::Api_DeactivatePlugin)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Write))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	PluginInterface * p = listOfPluginsByName.value(hRequest.GetArg("name"));
	if(!p)
		return new ApiAnswers::Error(Translator::tr("Unknown plugin '%1'", account).arg(hRequest.GetArg("name")));

	if(p->GetType() & PluginInterface::RequiredPlugin)
		return new ApiAnswers::Error(Translator::tr("Plugin '%1' can't be deactivated!", account).arg(hRequest.GetArg("name")));

	if(!p->GetEnable())
		return new ApiAnswers::Error(Translator::tr("Plugin '%1' is already disabled!", account).arg(hRequest.GetArg("name")));

	p->SetEnable(false);
	return new ApiAnswers::Ok(Translator::tr("'%1' is now disabled", account).arg(p->GetName()));
}

API_CALL(PluginManager::Api_UnloadPlugin)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Write))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QString name = hRequest.GetArg("name");
	if(UnloadPlugin(name))
	{
		return new ApiAnswers::Ok(Translator::tr("'%1' is now unloaded", account).arg(name));
	}
	else
		return new ApiAnswers::Error(Translator::tr("Can't unload '%1'!", account).arg(name));
}

API_CALL(PluginManager::Api_LoadPlugin)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Write))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QString filename = hRequest.GetArg("filename");
	if(LoadPlugin(filename))
		return new ApiAnswers::Ok(Translator::tr("'%1' is now loaded", account).arg(filename));
	else
		return new ApiAnswers::Error(Translator::tr("Can't load '%1'!", account).arg(filename));
}

API_CALL(PluginManager::Api_ReloadPlugin)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcServer,Account::Write))
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	QString name = hRequest.GetArg("name");
	if(ReloadPlugin(name))
		return new ApiAnswers::Ok(Translator::tr("'%1' is now reloaded", account).arg(name));
	else
		return new ApiAnswers::Error(Translator::tr("Can't reload '%1'!", account).arg(name));
}

API_CALL(PluginManager::Api_TTSLogPlugin)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "set")
	{
		if(!hRequest.HasArg("plugin"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("plugin"));

		QString plugin = hRequest.GetArg("plugin");

		if(!hRequest.HasArg("value"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("value"));

		bool value = hRequest.GetArg("value") == "enable" ? true : false;

		PluginInterface * p = listOfPluginsByName.value(plugin);

		p->SetTTSLog(value);
		return new ApiAnswers::Ok(Translator::tr("'%1' value is now '%2' for plugin '%3'", account).arg("TTSLog", Translator::tr(value ? "enabled" : "disabled"), p->GetName()));
	}
	else if(action == "get")
	{
		if(!hRequest.HasArg("plugin"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("plugin"));

		QString plugin = hRequest.GetArg("plugin");

		PluginInterface * p = listOfPluginsByName.value(plugin);

		bool value = p->GetTTSLog();
		return new ApiAnswers::Ok(Translator::tr("'%1' value is '%2' for plugin '%3'", account).arg("TTSLog", Translator::tr(value ? "enabled" : "disabled"), p->GetName()));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}


/********************
 * Required Plugins *
 ********************/

void PluginManager::RegisterAuthPlugin(PluginAuthInterface * p)
{
	if(!authPlugin)
		authPlugin = p;
	else
		LogWarning("An authentication plugin is already registered.");
}

void PluginManager::UnregisterAuthPlugin(PluginAuthInterface * p)
{
	if(authPlugin == p)
		authPlugin = 0;
	else
		LogWarning("Bad plugin during UnregisterAuthPlugin");
}
