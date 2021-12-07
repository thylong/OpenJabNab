#ifndef _PLUGINSIGNATURE_H_
#define _PLUGINSIGNATURE_H_

#include "plugininterface.h"

class PluginSignature
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.signature" )

public:
  PluginSignature();

  virtual bool Init(void) override;
  virtual void BeforeSendMessage(Bunny *, MessagePacket *, QString) override;

  virtual const QString GetVersion() { return "1.0.0"; }

private:
  virtual ~PluginSignature() = default;

  QByteArray GetBroadcastHTTPUserPath(Bunny *, QString);
  QMap<QString, QVariant> pluginSounds;

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_API_CALL(Api_PluginSound);
  PLUGIN_BUNNY_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_Sound);
};

#endif
