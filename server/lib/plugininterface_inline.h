
inline PluginInterface::PluginInterface(QString name, QString visualName, int type):pluginName(name), pluginType(type)
{
	// The visual name is more user-friendly (for visual-side only)
	if(visualName != QString())
		pluginVisualName = visualName;
	else
		pluginVisualName = name;
	// Create settings object
	QDir dir = QDir(GlobalSettings::GetConfigDir().append("/plugins/"));
	settings = new QSettings(dir.absoluteFilePath("plugin_"+pluginName+".ini"), QSettings::IniFormat);
	pluginEnable = GetSettings("pluginStatus/Enable", QVariant(true)).toBool();
	pluginTTSLog = GetSettings("pluginDebug/TTSLog", QVariant(true)).toBool();
	// Compute Plugin's Http path
	httpFolder = QString("%1/%2/%3").arg(GlobalSettings::GetString("Config/HttpRoot"), GlobalSettings::GetString("Config/HttpPluginsFolder"), pluginName);
	soundToSend.clear();
}

inline void PluginInterface::AddSoundToSend(Bunny * b, QString sound)
{
	QStringList list = soundToSend.value(b, QStringList());
	list.append(sound);
	soundToSend.insert(b, list);
}

inline QStringList PluginInterface::AdpFileToLoad(Bunny * b)
{
        QStringList list = soundToSend.value(b, QStringList());
        soundToSend.remove(b);
        return list;
}

inline bool PluginInterface::GetTTSLog() const
{
	return pluginTTSLog;
}

inline void PluginInterface::SetTTSLog(bool b)
{
	SetSettings("pluginDebug/TTSLog", b);
	pluginTTSLog = b;
}

inline void PluginInterface::TTSLog(QString const& bunny, QString const& what, TTSAnswer answer)
{
	TTSLog(bunny.leftJustified(12, ' ').left(12).toLatin1(), what, answer);
	//LogTTS(txt, bunny.leftJustified(12, " ").left(12).toLatin1(), what);
}

inline void PluginInterface::TTSLog(QByteArray const& bunny, QString const& what, TTSAnswer answer)
{
	if(pluginTTSLog)
	{
		//TTSLog::Log(bunny, what, txt);
		LogTTS(bunny, what, answer);
	}
}

inline PluginInterface::~PluginInterface()
{
	delete settings;
}

// Settings
inline QVariant PluginInterface::GetSettings(QString const& key, QVariant const& defaultValue) const
{
	return settings->value(key, defaultValue);
}

inline void PluginInterface::SetSettings(QString const& key, QVariant const& value, bool const& sync)
{
	settings->setValue(key, value);
	if(sync)
	{
		settings->sync();
	}
}

inline void PluginInterface::RemoveSettings(QString const& key, bool const& sync)
{
	if(key.contains("/*"))
	{
		QString group = key;
		group.replace(QString("/*"), QString(""));
		settings->beginGroup(group);
		settings->remove("");
		settings->endGroup();
	}
	else
	{
		settings->remove(key);
	}
	if(sync)
	{
		settings->sync();
	}
}

inline void PluginInterface::SetSettings(QString const& key, QVariant const& value)
{
	SetSettings(key, value, true);
}

inline void PluginInterface::RemoveSettings(QString const& key)
{
	RemoveSettings(key, true);
}

inline void PluginInterface::SyncSettings()
{
	settings->sync();
}

// Plugin's name
inline QString const& PluginInterface::GetName() const
{
	return pluginName;
}

inline QString const& PluginInterface::GetVisualName() const
{
	return pluginVisualName;
}

// Plugin enable/disable functions
inline bool PluginInterface::GetEnable() const
{
	return pluginEnable;
}

// Plugin type
inline int PluginInterface::GetType() const
{
	return pluginType;
}

inline bool PluginInterface::SupportExtendedApi() const
{
	return pluginType & ApiPlugin;
}

// Plugin enable/disable functions
inline void PluginInterface::SetEnable(bool newStatus)
{
	if(newStatus != pluginEnable)
	{
		pluginEnable = newStatus;
		SetSettings("pluginStatus/Enable", QVariant(newStatus));
		LogInfo(QString("Plugin %1 is now %2").arg(GetVisualName(), GetEnable() ? "enabled" : "disabled"));
		if(pluginType & BunnyV1Plugin || pluginType & BunnyV2Plugin)
			BunnyManager::PluginStateChanged(this);
		PluginStateChanged();
	}
}

// HTTP Data folder
inline QDir * PluginInterface::GetLocalHTTPFolder() const
{
	QDir pluginsFolder(GlobalSettings::GetString("Config/RealHttpRoot"));
	QString httpPluginsFolder = GlobalSettings::GetString("Config/HttpPluginsFolder");
	if (!pluginsFolder.cd(httpPluginsFolder))
	{
		if (!pluginsFolder.mkdir(httpPluginsFolder))
		{
			LogError(QString("Unable to create %1 directory !\n").arg(httpPluginsFolder));
			return NULL;
		}
		pluginsFolder.cd(httpPluginsFolder);
	}
	if (!pluginsFolder.cd(pluginName))
	{
		if (!pluginsFolder.mkdir(pluginName))
		{
			LogError(QString("Unable to create %1/%2 directory !\n").arg(httpPluginsFolder, pluginName));
			return NULL;
		}
		pluginsFolder.cd(pluginName);
	}
	return new QDir(pluginsFolder);
}

inline QByteArray PluginInterface::GetBroadcastHTTPPath(QString f) const
{
	return QString("broadcast/%1/%2").arg(httpFolder, f).toLatin1();
}

inline QByteArray PluginInterface::GetFullHTTPPath(QString f) const
{
	return QString("%1://%2/%3/%4").arg(GlobalSettings::GetString("OpenJabNabServers/HttpMode"), GlobalSettings::GetString("OpenJabNabServers/BroadServer"), httpFolder, f).toLatin1();
}

inline QString PluginInterface::cleanStringPertinence(QString str)
{
	return str.toLower().replace(QRegExp(QString::fromUtf8("[éèëê]")), "e").replace(QRegExp(QString::fromUtf8("[âäáàãå]")), "a").replace(QRegExp(QString::fromUtf8("[ç]")), "c").replace(QRegExp(QString::fromUtf8("[îïíì]")), "i").replace(QRegExp(QString::fromUtf8("[ñ]")), "n").replace(QRegExp(QString::fromUtf8("[ôöóòõø]")), "o").replace(QRegExp(QString::fromUtf8("[ß]")), "ss").replace(QRegExp(QString::fromUtf8("[ûüúù]")), "u").replace(QRegExp(QString::fromUtf8("[æ]")), "ae").replace(QRegExp(QString::fromUtf8("[œ]")), "oe").trimmed();
}

inline QStringList PluginInterface::cleanStringListPertinence(QStringList list)
{
	list.removeAll("de");
	list.removeAll("des");
	list.removeAll("le");
	list.removeAll("les");
	list.removeAll("la");
	list.removeAll("to");
	return list;
}

inline float PluginInterface::getPertinence(QString keywords, QString command)
{
	QStringList l1 = cleanStringListPertinence(cleanStringPertinence(keywords).replace(",", " ").split(" "));
	QStringList l2 = cleanStringListPertinence(cleanStringPertinence(command).replace(",", " ").split(" "));
	int c1 = l1.length();

	float words = 0;
	foreach(QString word, l1)
	{
		if(l2.contains(word))
			words++;
	}
	LogDebug("Pertinence between " + keywords + " and " + command + " : " + QString::number(words) + "/" + QString::number(c1));
	if(c1 != 0)
		return (float)( words / c1);
	return 0;
}

