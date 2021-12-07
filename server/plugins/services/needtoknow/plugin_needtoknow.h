#ifndef _PLUGINNEEDTOKNOW_H_
#define _PLUGINNEEDTOKNOW_H_

#include <QThread>

#include "plugininterface.h"
#include "pluginmessageinterface.h"
#include "browserclient.h"

class QNetworkReply;

class PluginNeedtoknow
  : public PluginInterface
  , public PluginMessageInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface PluginMessageInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.services.needtoknow" )

public:
  PluginNeedtoknow();

  QString OnApiGet(Bunny *, QVariant);
  void analyseDone(bool, Bunny*, QStringList, bool);

  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual bool OnRFID(Bunny * b, QByteArray const& tag) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;

  virtual const QString GetVersion(void) override { return "1.0.3"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "fr"; }
  virtual const QHash<QString, QString> GetChangelog(void) override;
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("get", "");
    return list;
  }

private:
  virtual ~PluginNeedtoknow() = default;

  BrowserClient _http;

  void createCron(Bunny *, int, QString);
  int GetRandomizedDelay(unsigned int);
  void getNTKPage(Bunny *, QString, bool);
  void getNTKPage(Bunny *, QString);
  void getNTKPage(Bunny *);

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_API_CALL(Api_Language);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_RFID);
};

class PluginNTK_WORKER
  : public QThread
{
  Q_OBJECT

signals:
  void done(bool, Bunny*, QStringList, bool);
public slots:
  void requestFinished(QNetworkReply* rep);

public:
  PluginNTK_WORKER(PluginNeedtoknow * ,Bunny * ,QString , bool);
  virtual ~PluginNTK_WORKER() = default;

private:
  PluginNeedtoknow * plugin;
  Bunny * bunny;
  QString language;
  bool save;
};
#endif
