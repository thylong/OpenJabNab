#ifndef _PLUGINCLOCK_H_
#define _PLUGINCLOCK_H_

#include <QMap>
#include <QDir>
#include <QStringList>

#include "plugininterface.h"

class PluginClock
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.clock" )

public:
  PluginClock();

  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual void OnCron(Bunny*, QVariant, unsigned int) override;
  virtual bool OnVoiceCommand(Bunny*, QString const&, QStringList const&) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;

  virtual const QString GetVersion(void) override { return "1.6.2"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "fr" << "us" << "uk" << "es" << "de" << "ca"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.6.0", "Add feature to disable hourly clock");
    revisions.insert("1.6.1", "Update plugin for v1 click");
    revisions.insert("1.6.2", "Fix bug for violet voices");
    return revisions;
  }

  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("get", "");
    return list;
  }

private:
  virtual ~PluginClock();

  enum Type { Type_Voice, Type_HourlyBell, Type_SemiHourlyBell, Type_None};
  QString OnApiGet(Bunny *, QVariant);

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_Setup);
  PLUGIN_BUNNY_API_CALL(Api_Voice);
  PLUGIN_BUNNY_API_CALL(Api_SetVoice);
  PLUGIN_BUNNY_API_CALL(Api_GetVoiceList);

  bool sayTime(Bunny *);
  QDir clockFolder;
  QMap<Bunny*, QString> bunnyList;
  QStringList availableVoices;
};

#endif
