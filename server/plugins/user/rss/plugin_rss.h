#ifndef _PLUGINRSS_H_
#define _PLUGINRSS_H_

#include <QThread>

#include "plugininterface.h"
#include "pluginmessageinterface.h"

class QNetworkReply;

class PluginRss
  : public PluginInterface
  , private PluginMessageInterface
{
  friend class PluginRss_Parser;
  friend class PluginRss_Player;

  Q_OBJECT
  Q_INTERFACES(PluginInterface PluginMessageInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.rss" )

public:
  PluginRss();

  void analyseXml(QNetworkReply*);
  void playerDone(bool, Bunny *, QStringList, bool);
  void analyseDone(bool, Bunny *, QStringList, QString);

  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual bool OnRFID(Bunny * b, QByteArray const& tag) override;
  virtual void OnCron(Bunny *, QVariant, unsigned int) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual const QString GetVersion(void) override { return "0.1.1"; }

private:
  virtual ~PluginRss();

  void readFeeds(Bunny *, bool);
  void readFeeds(Bunny *);
  void readFeed(QString, Bunny *, bool);
  void readFeed(QString, Bunny *);
  void RegisterCrons(Bunny *);
  void CleanCrons(Bunny *);

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Feed);
  PLUGIN_BUNNY_API_CALL(Api_Custom);
  PLUGIN_BUNNY_API_CALL(Api_Language);
};

class PluginRss_Parser
  : public QThread
{
  Q_OBJECT

signals:
  void done(bool, Bunny*, QStringList, QString);

public:
  PluginRss_Parser(PluginRss *, QString, QString, Bunny *);
  void run();

private:
  virtual ~PluginRss_Parser() = default;

  PluginRss * plugin;
  QString feed;
  QString content;
  Bunny * bunny;
};

class PluginRss_Player
  : public QThread
{
  Q_OBJECT

signals:
  void done(bool, Bunny*, QStringList, bool);

public:
  PluginRss_Player(PluginRss * , Bunny *, QStringList, bool);
  void run();

private:
  virtual ~PluginRss_Player() = default;

  PluginRss * plugin;
  Bunny * bunny;
  QStringList titles;
  bool save;
};

#endif
