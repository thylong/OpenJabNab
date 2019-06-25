#ifndef _PLUGINFAIRYTALE_H_
#define _PLUGINFAIRYTALE_H_

#include <QMap>
#include "plugininterface.h"
	
class PluginFairytale : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
	Q_PLUGIN_METADATA(IID "ojn.plugin.user.fairytale" )
  
public:
	PluginFairytale();
	virtual ~PluginFairytale();
	virtual bool Init();
	void OnCron(Bunny *, QVariant, unsigned int);
	virtual bool OnRFID(Bunny *, QByteArray const&);
	virtual bool OnRFID(Ztamp *, Bunny *);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "1.0.0"; }
	
	bool OnClick(Bunny *, PluginInterface::ClickType);

	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);

	// API
	void InitApiCalls();

	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Preset);
	PLUGIN_BUNNY_API_CALL(Api_Schedule);
	PLUGIN_BUNNY_API_CALL(Api_Url);
	PLUGIN_API_CALL(Api_PluginPreset);

private:
	bool streamFairytale(Bunny *, QString);
	bool streamRandomFairytale(Bunny *, bool, bool);
	bool streamPresetFairytale(Bunny *, QString);
	QMap<QString, QVariant> presets;
};

#endif
