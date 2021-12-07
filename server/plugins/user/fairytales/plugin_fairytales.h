#ifndef _PLUGINFAIRYTALE_H_
#define _PLUGINFAIRYTALE_H_

#include <QMap>
#include "plugininterface.h"

class PluginFairytale
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.fairytale" )

public:
  PluginFairytale();

  virtual bool Init(void) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnRFID(Ztamp *, Bunny *) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;

  virtual const QString GetVersion(void) override { return "1.0.0"; }

private:
  virtual ~PluginFairytale();

  bool streamFairytale(Bunny *, QString);
  bool streamRandomFairytale(Bunny *, bool, bool);
  bool streamPresetFairytale(Bunny *, QString);

  QMap<QString, QVariant> presets;

  // API
  void InitApiCalls();

  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Preset);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_Url);
  PLUGIN_API_CALL(Api_PluginPreset);
};

#endif
