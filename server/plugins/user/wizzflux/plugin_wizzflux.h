#ifndef _PluginWizzflux_H_
#define _PluginWizzflux_H_

#include "plugininterface.h"

class QNetworkReply;

class PluginWizzflux
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.wizzflux" )

public:
  PluginWizzflux();

  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnRFID(Ztamp *, Bunny *) override;

  virtual const QString GetVersion(void) override { return "1.1.0"; }

private:
  virtual ~PluginWizzflux();

  void analyse(QNetworkReply*);
  bool streamFlux(Bunny *, QString const);
  QStringList Flist;

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
};


#endif
