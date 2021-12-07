#ifndef _PLUGINWEBRADIO_H_
#define _PLUGINWEBRADIO_H_

#include <QMap>
#include "plugininterface.h"

class PluginWebradio
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.webradio" )

public:
  PluginWebradio();
  virtual bool Init(void) override;

  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnRFID(Ztamp *, Bunny *) override;

  virtual const QString GetVersion(void) override { return "2.1.1"; }
private:
  virtual ~PluginWebradio();

  bool streamWebradio(Bunny *, QString);
  bool streamPresetWebradio(Bunny *, QString);
  QMap<QString, QVariant> presets;
  QString getRadioName(QString);

  // API
  void InitApiCalls();

  PLUGIN_API_CALL(Api_PluginPreset);
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Preset);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_Url);
};

#endif
