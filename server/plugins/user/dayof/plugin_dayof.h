#ifndef _PLUGINDAYOF_H_
#define _PLUGINDAYOF_H_

#include <QMap>
#include <QMultiMap>
#include <QTextStream>
#include <QThread>

#include "pluginmessageinterface.h"

class PluginDayof
  : public PluginInterface
  , public PluginMessageInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface PluginMessageInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.dayof" )

public:
  PluginDayof();

  virtual bool OnClick(Bunny *, PluginInterface::ClickType);
  virtual void OnCron(Bunny *, QVariant, unsigned int);
  virtual void OnBunnyConnect(Bunny *);
  virtual void OnBunnyDisconnect(Bunny *);
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
  virtual bool OnRFID(Bunny *, QByteArray const&);

  virtual const QString GetVersion(void) override { return "1.0.1"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "fr" ; }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("get", "");
    return list;
  }

public slots:
  QString OnApiGet(Bunny *, QVariant);

private:
  virtual ~PluginDayof();

  void sayDayof(Bunny *, bool);
  void sayDayof(Bunny *);
  void InitData();

  QMap<int, QMultiMap<int, QString> > data;

  // API
  void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_Language);
};

#endif
