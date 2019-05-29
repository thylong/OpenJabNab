#ifndef _PLUGINPACKET_H_
#define _PLUGINPACKET_H_

#include "plugininterface.h"

class PluginPacket : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.packet" )

public:
	PluginPacket();
	virtual ~PluginPacket() {};
	QString GetVersion() { return "1.2.1"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.2.1", "Add supported languages informations");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_SendAmbient);
	PLUGIN_BUNNY_API_CALL(Api_SendPacket);
	PLUGIN_BUNNY_API_CALL(Api_SendMessage);
	PLUGIN_BUNNY_API_CALL(Api_SendExpert);
	PLUGIN_API_CALL(Api_SendServerMessage);
};

#endif
