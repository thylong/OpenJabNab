#ifndef _PLUGINDEBUG_H_
#define _PLUGINDEBUG_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>
#include "plugininterface.h"
#include "httprequest.h"

class PluginDebug : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)

    Q_PLUGIN_METADATA(IID "ojn.plugin.system.debug" FILE "")

public:
	PluginDebug();
	virtual ~PluginDebug();
	void InitApiCalls();
	bool XmppBunnyMessage(Bunny *, QByteArray const&);
	QString GetVersion() { return "0.1.0"; }

protected:
	PLUGIN_BUNNY_API_CALL(Api_Info);
	PLUGIN_API_CALL(Api_Config);
private:
};

#endif
