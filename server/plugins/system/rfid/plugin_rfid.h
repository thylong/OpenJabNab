#ifndef _PLUGINRFID_H_
#define _PLUGINRFID_H_

#include "plugininterface.h"
#include "httprequest.h"

class PluginRFID : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.system.rfid" FILE "")

public:
	PluginRFID();
	virtual ~PluginRFID() {};
	virtual bool HttpRequestHandle(HTTPRequest &);
	void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &);

	QString GetVersion() { return "1.1.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.1.0", "Add support for bad bunnies");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	void InitApiCalls();
protected:
        PLUGIN_BUNNY_API_CALL(Api_Config);
	PLUGIN_API_CALL(Api_GetLastTag);
	PLUGIN_API_CALL(Api_GetLastTagForBunny);
	PLUGIN_BUNNY_API_CALL(Api_GetLastBunnyTag);
};

#endif
