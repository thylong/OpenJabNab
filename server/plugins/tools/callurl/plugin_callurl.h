#ifndef _PLUGINCALLURL_H_
#define _PLUGINCALLURL_H_

#include "plugininterface.h"

class PluginCallURL
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.tool.callurl")

public:
  PluginCallURL();

  void CallURL(Bunny *, QString) ;

  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual bool OnRFID(Bunny * b, QByteArray const& tag) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnEarsMove(Bunny *, int, int) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;

  virtual const QString GetVersion(void) override { return "2.0.5"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("2.0.5", "Add supported languages informations");
    return revisions;
  }

private:
  virtual ~PluginCallURL();

  QString GetURL(Bunny *, QString);
  QString MakeNextName(Bunny *);

  // API
  void InitApiCalls();
  PLUGIN_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_Url);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Voice);
  PLUGIN_BUNNY_API_CALL(Api_Ear);
};

#endif
