#ifndef _PLUGINSTATUS_H_
#define _PLUGINSTATUS_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>
#include "plugininterface.h"
#include "httprequest.h"

class PluginStatus : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.system.status" )

public:
	PluginStatus();
	virtual ~PluginStatus();
	void InitApiCalls();
	bool XmppBunnyMessage(Bunny *, QByteArray const&);
	void SendRequestStatus(Bunny *);
	void SendRequestStatus(Bunny *, QString);
	virtual void OnBunnyConnect(Bunny *);
	QString GetVersion() { return "0.2.0"; }

protected:
	PLUGIN_BUNNY_API_CALL(Api_Status);
	PLUGIN_API_CALL(Api_Config);
private:
};

#endif
