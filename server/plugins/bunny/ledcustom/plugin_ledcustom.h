#ifndef _PLUGINLEDC_H_
#define _PLUGINLEDC_H_

#include <QMultiMap>
#include <QTextStream>
#include <QDateTime>
#include "plugininterface.h"


class PluginLedcustom
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.ledcustom" )

//signals:
  // weather
  //void launchWeatherUpdate(QString);
  // stock
  //void launchStockUpdate(QString);

public:
  PluginLedcustom();

  virtual void OnBunnyConnect(Bunny *) override;
  virtual bool XmppBunnyMessage(Bunny *, QByteArray const&) override;

  virtual const QString GetVersion(void) override { return "1.1.2"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override;
  virtual const int GetBootcodeRequirement(void) override { return 88; }

private:
  virtual ~PluginLedcustom() = default;

  void SendChoregraphiesRequest(Bunny *);
  void updateBunny(Bunny *);
  void updateChoregraphies(Bunny *);


  typedef struct
  {
    int service;
    int value;
    int tempo;
    QString leds;
  } Chor;

  typedef struct
  {
    QByteArray bunnyId;
    QDateTime updated;
    QList<Chor> chors;
  } BunnyData;

  QMap<QByteArray, BunnyData> services;

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_Service);
  PLUGIN_BUNNY_API_CALL(Api_Chor);
};

#endif
