#ifndef _PLUGINDICTON_H_
#define _PLUGINDICTON_H_

#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginDicton
  : public PluginInterface
  , private PluginMessageInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface PluginMessageInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.dicton" )

public:
  PluginDicton();

  QString OnApiGet(Bunny *, QVariant);

  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;

  virtual const QString GetVersion(void) override { return "1.0.3"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "fr" ; }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("get", "");
    return list;
  }

private:
  virtual ~PluginDicton();

  void sayDicton(Bunny *, bool);
  void sayDicton(Bunny *);
  void InitData();

  QMap<int, QMap<int, QString> > data;

  // API
  void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_Language);
};

#endif
