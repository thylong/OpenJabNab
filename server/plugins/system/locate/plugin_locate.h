#ifndef _PLUGINLOCATE_H_
#define _PLUGINLOCATE_H_

#include "plugininterface.h"
#include "httprequest.h"

class PluginLocate : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)

    Q_PLUGIN_METADATA(IID "ojn.plugin.system.locate")

public:
	PluginLocate();
	virtual ~PluginLocate() {};
	virtual bool HttpRequestHandle(HTTPRequest &);
	void OnBunnyConnect(Bunny *);
	QString GetVersion() { return "1.6.1"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.2.1", "Save bootcode version in bunny");
		revisions.insert("1.3.0", "Get platform for bad configured bunnies");
		revisions.insert("1.3.1", "Better API");
		revisions.insert("1.3.2", "Add API to change bunny config");
		revisions.insert("1.4.0", "Add function to remotely reconfigure bunny");
		revisions.insert("1.5.0", "Add new fields in locate string");
		revisions.insert("1.6.0", "Add relocate feature, and list of bunnies");
		revisions.insert("1.6.1", "Add feature to change wifi setup");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	virtual void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_SetCustomLocateSetting);
	PLUGIN_BUNNY_API_CALL(Api_GetCustomLocateSetting);
	PLUGIN_BUNNY_API_CALL(Api_BunnyServer);
	PLUGIN_BUNNY_API_CALL(Api_BunnyConfig);
	PLUGIN_BUNNY_API_CALL(Api_BunnyCustom);
	PLUGIN_API_CALL(Api_Server);
private:
	QStringList configList;
	QStringList customList;
	QStringList waitingBunnies;
	QStringList failingBunnies;
};

#endif
