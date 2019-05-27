#ifndef _PLUGINTTS_H_
#define _PLUGINTTS_H_

#include "apimanager.h"
#include "plugininterface.h"

class PluginTTS : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.tts" FILE "")

public:
	PluginTTS();
	virtual ~PluginTTS() {};
	QString GetVersion() { return "1.2.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.2.0", "Add support for Nabaztag V1");
		return revisions;
	}
	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Say);

};

#endif
