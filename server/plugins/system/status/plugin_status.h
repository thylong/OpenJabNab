#ifndef _PLUGINSTATUS_H_
#define _PLUGINSTATUS_H_

#include "plugininterface.h"

class PluginStatus
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.system.status" )

public:
  PluginStatus();

  virtual void OnBunnyConnect(Bunny *) override;
  virtual bool XmppBunnyMessage(Bunny *, QByteArray const&) override;

  virtual const QString GetVersion(void) override { return "0.2.0"; }

private:
  virtual ~PluginStatus() = default;

  void SendRequestStatus(Bunny *);
  void SendRequestStatus(Bunny *, QString);

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_Status);
};

#endif
