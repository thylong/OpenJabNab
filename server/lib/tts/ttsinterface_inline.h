
#define TTS_FOLDER "tts"

inline TTSInterface::TTSInterface(QString name, QString visualName):ttsName(name)
{
	// The visual name is more user-friendly (for visual-side only)
	if(visualName != QString())
		ttsVisualName = visualName;
	else
		ttsVisualName = name;
	// Create settings object
	QDir dir = QDir(GlobalSettings::GetConfigDir().append("/tts/"));
	settings = new QSettings(dir.absoluteFilePath("tts_"+ttsName+".ini"), QSettings::IniFormat);
	ttsEnable = GetSettings("ttsStatus/Enable", QVariant(true)).toBool();

	// Folder - Use TTSRoot for TTS files, fallback to RealHttpRoot if not set
	QDir folder(GlobalSettings::GetString("Config/TTSRoot", GlobalSettings::GetString("Config/RealHttpRoot")));
	// Try to create tts subfolder
	if (!folder.cd(TTS_FOLDER))
	{
		if (!folder.mkdir(TTS_FOLDER))
		{
			LogError(QString("Unable to create " TTS_FOLDER " directory !\n"));
			return;
		}
		folder.cd(TTS_FOLDER);
	}
	if (!folder.cd(name))
	{
		if (!folder.mkdir(name))
		{
			LogError(QString("Unable to create %1 directory !\n").arg(name));
			return;
		}
		folder.cd(name);
	}
	ttsFolder = folder;
	ttsHTTPUrl = TTS_FOLDER"/"+name+"/%1/%2"; // %1 For voice, %2 for FileName
	// Compute TTS's Http path
}

inline TTSInterface::~TTSInterface()
{
	delete settings;
}

// Settings
inline QVariant TTSInterface::GetSettings(QString const& key, QVariant const& defaultValue) const
{
	return settings->value(key, defaultValue);
}

inline void TTSInterface::SetSettings(QString const& key, QVariant const& value)
{
	settings->setValue(key, value);
	settings->sync();
}

// TTS's name
inline QString const& TTSInterface::GetName() const
{
	return ttsName;
}

inline QString const& TTSInterface::GetVisualName() const
{
	return ttsVisualName;
}

// TTS enable/disable functions
inline bool TTSInterface::GetEnable() const
{
	return ttsEnable;
}

// TTS enable/disable functions
inline void TTSInterface::SetEnable(bool newStatus)
{
	if(newStatus != ttsEnable)
	{
		ttsEnable = newStatus;
		SetSettings("ttsStatus/Enable", QVariant(newStatus));
		LogInfo(QString("TTS %1 is now %2").arg(GetVisualName(), GetEnable() ? "enabled" : "disabled"));
	}
}

inline QStringList TTSInterface::GetLanguageList()
{
	QMap<QString, Voice>::iterator i;
	QStringList list;
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		list.append(i.value().language);
	}
	list.removeDuplicates();
	list.sort();
	return list;
}

inline QStringList TTSInterface::GetVoiceList(QString language)
{
	QMap<QString, Voice>::iterator i;
	QStringList list;
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		if(i.value().language == language)
		{
			list.append(i.key());
		}
	}
	return list;
}

inline QMap<QString, QString> TTSInterface::GetVoiceListWithName(QString language)
{
	QMap<QString, Voice>::iterator i;
	QMap<QString, QString> list;
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		if(i.value().language == language)
		{
			list.insert(i.key(), i.value().toString());
		}
	}
	return list;
}

inline QMap<QString, QMap<QString, QString> > TTSInterface::GetAllVoices()
{
	QMap<QString, Voice>::iterator i;
	QMap<QString, QMap<QString, QString> > list;
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		QMap<QString, QString> l = list.value(i.value().language);
		l.insert(i.key(), i.value().toString());
		list.insert(i.value().language, l);
	}
	return list;
}

inline Voice TTSInterface::GetVoice(QString name)
{
	if(voiceList.contains(name))
	{
		return voiceList.value(name);
	}
	Voice v;
	v.name = "None";
	v.language = "";
	return v;
}

inline Voice TTSInterface::GetBestVoice(QString language)
{
	QMap<QString, Voice>::iterator i;
	//LogDebug("Finding best possibility");
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		if(i.value().language == language)
		{
			//LogDebug("Found perfect match : " + i.key());
			return i.value();
		}
	}
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		//LogDebug("Giving first match : " + i.key());
		return i.value();
	}
	Voice v;
	v.name = "None";
	v.language = "";
	return v;
}

inline Voice TTSInterface::GetBestVoice(QString language, Voice::VoiceGenre genre)
{
	QMap<QString, Voice>::iterator i;
	//LogDebug("Finding best possibility");
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		if(i.value().language == language && i.value().genre == genre)
		{
			//LogDebug("Found perfect match : " + i.key());
			return i.value();
		}
	}
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		if(i.value().language == language)
		{
			//LogDebug("Found language match : " + i.key());
			return i.value();
		}
	}
	for (i = voiceList.begin(); i != voiceList.end(); ++i)
	{
		//LogDebug("Giving first match : " + i.key());
		return i.value();
	}
	Voice v;
	v.name = "None";
	v.language = "";
	return v;
}
