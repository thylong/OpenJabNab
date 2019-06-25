#ifndef _PLUGINFAIRYTALE_H_
#define _PLUGINFAIRYTALE_H_

#include <QMap>
#include "plugininterface.h"
	
class PluginLafontaine : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.lafontaine" )
	
public:
	PluginLafontaine();
	virtual ~PluginLafontaine();
	virtual bool Init();
	void OnCron(Bunny *, QVariant, unsigned int);
	virtual bool OnRFID(Bunny *, QByteArray const&);
	virtual bool OnRFID(Ztamp *, Bunny *);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "1.0.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.0.0", "Initial version");
		return revisions;
	}
	
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
	bool streamLafontaine(Bunny *, QString);
	bool streamRandomLafontaine(Bunny *, bool, bool);
	bool streamPresetLafontaine(Bunny *, QString);
	QMap<QString, QVariant> presets;
};

#endif
