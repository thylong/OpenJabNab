#ifndef _PLUGINSLEEP_H_
#define _PLUGINSLEEP_H_

#include <QList>
#include <QPair>
#include <QStringList>
#include <QTime>

#include "plugininterface.h"
#include "sleeptime.h"

class PluginSleep
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.sleep" )

public:
  PluginSleep();

  void OnCronSleep(Bunny *, QVariant, unsigned int);
  void OnCronWakeUp(Bunny *, QVariant, unsigned int);

  const QString GetVersion(void) override { return "2.1.0"; }
  const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.4.3", "Add supported languages informations");
    revisions.insert("2.0.0", "Customizable sleeps");
    revisions.insert("2.1.0", "Add support for Nabaztag V1");
    return revisions;
  }

  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual bool OnRFID(Bunny * b, QByteArray const& tag) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;
  virtual bool OnEarsMove(Bunny *, int, int) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual void OnInitPacket(const Bunny * b, AmbientPacket &, SleepPacket &) override;
  virtual void SetServices(Bunny *) override;

  bool NeedToSleep(const Bunny *);

private:
  virtual ~PluginSleep();

  void RegisterCrons(Bunny *);
  void CleanCrons(Bunny *);
  void UpdateState(Bunny *);
  void ConvertConf(Bunny *);

  SleepTime getSleepTime(QString);
  QString fromSleepTime(SleepTime);

  QList<SleepTime> getSleepTimes(QStringList);
  QStringList fromSleepTimes(QList<SleepTime>);

  QList<SleepTime> addSleepTime(QList<SleepTime>, SleepTime);
  QList<SleepTime> compactSleepTime(QList<SleepTime>);

  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_Sleep);
  PLUGIN_BUNNY_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  // PLUGIN_BUNNY_API_CALL(Api_Wakeup);
  // PLUGIN_BUNNY_API_CALL(Api_Setup);
  // PLUGIN_BUNNY_API_CALL(Api_GetSetup);
};
/*
Q_DECLARE_METATYPE(SleepTime)
namespace QVariantHelper
{
    inline SleepTime ToSleepTime(QVariant v) { return v.value<SleepTime>(); }
};*/
#endif
