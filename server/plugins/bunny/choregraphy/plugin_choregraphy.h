#ifndef _PLUGINCHOREGRAPHY_H_
#define _PLUGINCHOREGRAPHY_H_

#include "plugininterface.h"

class PluginChoregraphy : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.choregraphy" FILE "")

public:
	PluginChoregraphy();
	virtual ~PluginChoregraphy();

	virtual bool Init();

	void BeforeSendMessage(Bunny *, MessagePacket *, QString);

	QString GetVersion() { return "1.0.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.0.0", "Initial revision");
		return revisions;
	}

	QStringList GetLanguages() { return QStringList() << "all"; }

	// API
	virtual void InitApiCalls();

	PLUGIN_BUNNY_API_CALL(Api_Config);

};

#endif
