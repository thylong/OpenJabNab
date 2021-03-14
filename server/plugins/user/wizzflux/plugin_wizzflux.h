#ifndef _PluginWizzflux_H_
#define _PluginWizzflux_H_

#include "plugininterface.h"

class QNetworkReply;

class PluginWizzflux : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.wizzflux" )

public:
	PluginWizzflux();
	virtual ~PluginWizzflux();
	virtual bool Init();
	void OnCron(Bunny *, QVariant, unsigned int);
	virtual bool OnRFID(Bunny *, QByteArray const&);
	virtual bool OnRFID(Ztamp *, Bunny *);
	QString GetVersion() { return "1.1.0"; }

	bool OnClick(Bunny *, PluginInterface::ClickType);

	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_AddRFID);
	PLUGIN_BUNNY_API_CALL(Api_RemoveRFID);
	PLUGIN_BUNNY_API_CALL(Api_AddWebcast);
	PLUGIN_BUNNY_API_CALL(Api_RemoveWebcast);
	PLUGIN_BUNNY_API_CALL(Api_GetDefault);
	PLUGIN_BUNNY_API_CALL(Api_SetDefault);
	PLUGIN_BUNNY_API_CALL(Api_Play);
	PLUGIN_BUNNY_API_CALL(Api_ListWebcast);
	PLUGIN_BUNNY_API_CALL(Api_ListFlux);
	PLUGIN_API_CALL(Api_GetFlux);
	PLUGIN_API_CALL(Api_SetFlux);
private:
	bool streamFlux(Bunny *, QString const);
    QStringList Flist;
private slots:
	void analyse(QNetworkReply*);
};


#endif
