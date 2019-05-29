#ifndef _PLUGINSTATS_H_
#define _PLUGINSTATS_H_

#include "plugininterface.h"

class PluginStats : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.system.stats" )

private slots:
	void AddApiCount();

public:
	PluginStats();
	virtual ~PluginStats();

	void OnCron(Bunny *, QVariant, unsigned int);
	virtual bool OnRFID(Ztamp *, Bunny *);
	bool OnClick(Bunny *, PluginInterface::ClickType);
	bool OnEarsMove(Bunny *, int, int);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	bool OnRecord(Bunny *, QString const&);

	virtual void InitApiCalls();
	PLUGIN_API_CALL(Api_GetColors);
	PLUGIN_API_CALL(Api_GetPlugins);
	PLUGIN_API_CALL(Api_GetBunniesIP);
	PLUGIN_API_CALL(Api_GetBunniesTimezone);
	PLUGIN_API_CALL(Api_GetBunniesName);
	PLUGIN_API_CALL(Api_GetBunniesStatus);
        PLUGIN_API_CALL(Api_GetBunniesInformation);
	PLUGIN_API_CALL(Api_GetCounters);
	PLUGIN_API_CALL(Api_GetWidgetJson);

private:
	int singleClick;
	int doubleClick;
	int rfid;
	int ears;
	int voice;
	int record;
	int api;
};

#endif
