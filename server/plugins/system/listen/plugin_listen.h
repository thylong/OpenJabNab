#ifndef _PLUGINLISTEN_H_
#define _PLUGINLISTEN_H_

#include <QDateTime>
#include <QMap>

#include "plugininterface.h"

class PluginListen
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)

  Q_PLUGIN_METADATA(IID "ojn.plugin.system.listen" )

public:
  PluginListen();

  virtual void OnBunnyConnect(Bunny *) override;
  virtual bool XmppBunnyMessage(Bunny *, QByteArray const&) override;
  virtual void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &) override;

  virtual const QString GetVersion(void) override { return "1.0.0"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }

private:
  virtual ~PluginListen() = default;

  void SendListeningGain(Bunny *);

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_Config);

  typedef struct {
    Bunny * bunny;
    unsigned int volume;
    QDateTime date;
  } ListenElement;

  QMap<QByteArray, ListenElement> listenList;
};

#endif
