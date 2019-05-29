#ifndef _PLUGINSIGNATURE_H_
#define _PLUGINSIGNATURE_H_

#include "plugininterface.h"

class PluginSignature : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.signature" )

public:
	PluginSignature();
	virtual ~PluginSignature();

	virtual bool Init();

	void BeforeSendMessage(Bunny *, MessagePacket *, QString);

	QString GetVersion() { return "1.0.0"; }

	// API
	virtual void InitApiCalls();

	PLUGIN_BUNNY_API_CALL(Api_Config);
	PLUGIN_BUNNY_API_CALL(Api_Sound);
	PLUGIN_API_CALL(Api_PluginSound);

private:
	QByteArray GetBroadcastHTTPUserPath(Bunny *, QString);
	QMap<QString, QVariant> pluginSounds;
};

#endif
