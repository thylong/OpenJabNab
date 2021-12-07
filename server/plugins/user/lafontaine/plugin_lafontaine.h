#ifndef _PLUGINFAIRYTALE_H_
#define _PLUGINFAIRYTALE_H_

#include <QMap>
#include "plugininterface.h"

class PluginLafontaine
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.lafontaine" )

public:
  PluginLafontaine();

  virtual bool Init(void) override;
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnRFID(Ztamp *, Bunny *) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;

  virtual const QString GetVersion(void) override { return "1.0.0"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.0.0", "Initial version");
    return revisions;
  }

private:
  virtual ~PluginLafontaine();

  bool streamLafontaine(Bunny *, QString);
  bool streamRandomLafontaine(Bunny *, bool, bool);
  bool streamPresetLafontaine(Bunny *, QString);
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
