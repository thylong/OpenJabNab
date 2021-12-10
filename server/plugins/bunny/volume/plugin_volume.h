#ifndef _PluginVolume_H_
#define _PluginVolume_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>

#include "plugininterface.h"

class PluginVolume
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.volume" )

public:
  PluginVolume();

  virtual void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool XmppBunnyMessage(Bunny *, QByteArray const&) override;

  virtual const QString GetVersion(void) override { return "1.3.2"; }
  virtual const QHash<QString, QString> GetChangelog(void) override;
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("set", "string");
    list.insert("get", "xml");
    return list;
  }

public slots:
  QString OnApiGet(Bunny *, QVariant);
  QString OnApiSet(Bunny *, QVariant);

private:
  virtual ~PluginVolume();

  void RegisterCrons(Bunny *);
  void CleanCrons(Bunny *);

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_Sound);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
};

#endif
