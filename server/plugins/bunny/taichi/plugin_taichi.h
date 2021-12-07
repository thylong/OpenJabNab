#ifndef _PLUGINTAICHI_H_
#define _PLUGINTAICHI_H_

#include "plugininterface.h"

class PluginTaichi
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.taichi" )

public:
  PluginTaichi();

  QString OnApiTaichi(Bunny *, QVariant);

  virtual void OnBunnyConnect(Bunny *);
  virtual void OnBunnyDisconnect(Bunny *);
  virtual void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &);
  virtual bool OnRFID(Bunny * b, QByteArray const& tag);
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
  virtual void SetServices(Bunny *) override;

  virtual const QString GetVersion() { return "1.2.2"; }
  virtual const QStringList GetLanguages() { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog()
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.2.2", "Add support for Nabaztag V1");
    return revisions;
  }
  virtual const QHash<QString, QString> GetExtendedApiFunctions()
  {
    QHash<QString, QString> list;
    list.insert("taichi", "");
    return list;
  }

private:
  virtual ~PluginTaichi() = default;

  void SendTaichiFrequency(Bunny *);

  // API
  void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_SetFrequency);
  PLUGIN_BUNNY_API_CALL(Api_GetFrequency);
  PLUGIN_BUNNY_API_CALL(Api_SetRFID);
};

#endif
