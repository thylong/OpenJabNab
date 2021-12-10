#ifndef _PLUGINSURPRISE_H_
#define _PLUGINSURPRISE_H_

#include "plugininterface.h"

class PluginSurprise
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.surprise" )

public:
  PluginSurprise();

  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;
  virtual const QString GetVersion(void) override { return "2.4.1"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "fr" << "en" << "es" << "it" << "de"; }
  virtual const QHash<QString, QString> GetChangelog(void) override;
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("speak", "");
    return list;
  }

public slots:
  QString OnApiSpeak(Bunny *, QVariant);

private:
  virtual ~PluginSurprise() = default;

  bool PlaySurprise(Bunny *, QString);
  void createCrons(Bunny *);
  void createCron(Bunny *, int, QString);
  int GetRandomizedFrequency(unsigned int);

  // API
  virtual void InitApiCalls(void) override;

  PLUGIN_BUNNY_API_CALL(Api_GetFolderList);
  PLUGIN_BUNNY_API_CALL(Api_SetSurprise);
  PLUGIN_BUNNY_API_CALL(Api_GetSurprises);
  PLUGIN_BUNNY_API_CALL(Api_DelSurprise);

  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Surprise);
  PLUGIN_BUNNY_API_CALL(Api_Folder);

  QStringList availableSurprises;
};

#endif
