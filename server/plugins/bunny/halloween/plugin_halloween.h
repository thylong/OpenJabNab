#ifndef _PLUGINHALLOWEEN_H_
#define _PLUGINHALLOWEEN_H_

#include "plugininterface.h"

class PluginHalloween
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.halloween" )

public:
  PluginHalloween();

  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;

  virtual const QString GetVersion(void) override { return "1.0.0"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "fr" << "en" << "es" << "it" << "de"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.0.0", "Initial version (clone from surprise)");
    return revisions;
  }

  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("speak", "");
    return list;
  }

private:
  virtual ~PluginHalloween() = default;

  QString OnApiSpeak(Bunny *, QVariant);

  bool PlaySound(Bunny *);
  void createCron(Bunny *, int, int);
  int GetRandomizedDelay(unsigned int, unsigned int);

  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_Sound);
  //PLUGIN_BUNNY_API_CALL(Api_RFID);
  //PLUGIN_BUNNY_API_CALL(Api_Folder);
};

#endif
