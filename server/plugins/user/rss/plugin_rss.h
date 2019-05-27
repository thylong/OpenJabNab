#ifndef _PLUGINRSS_H_
#define _PLUGINRSS_H_

#include <QUrl>
#include <QMultiMap>
#include <QNetworkReply>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginRss : public PluginInterface, PluginMessageInterface
{
	friend class PluginRss_Parser;
	friend class PluginRss_Player;
	Q_OBJECT
	Q_INTERFACES(PluginInterface PluginMessageInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.user.rss" FILE "")

private slots:
	void analyseXml(QNetworkReply*);
	void playerDone(bool, Bunny *, QStringList, bool);
	void analyseDone(bool, Bunny *, QStringList, QString);
public:
	PluginRss();
	virtual ~PluginRss();

	bool OnClick(Bunny *, PluginInterface::ClickType);
	bool OnRFID(Bunny * b, QByteArray const& tag);
	void OnCron(Bunny *, QVariant, unsigned int);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	void AfterBunnyUnregistered(Bunny *) {};
	QString GetVersion() { return "0.1.1"; }

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Schedule);
	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Feed);
	PLUGIN_BUNNY_API_CALL(Api_Custom);
	PLUGIN_BUNNY_API_CALL(Api_Language);
private:

	void readFeeds(Bunny *, bool);
	void readFeeds(Bunny *);
	void readFeed(QString, Bunny *, bool);
	void readFeed(QString, Bunny *);
	void RegisterCrons(Bunny *);
	void CleanCrons(Bunny *);
};

class PluginRss_Parser : public QThread
{
	Q_OBJECT

signals:
	void done(bool, Bunny*, QStringList, QString);

public:
	PluginRss_Parser(PluginRss *, QString, QString, Bunny *);
	virtual ~PluginRss_Parser() {}
	void run();

private:
	PluginRss * plugin;
	QString feed;
	QString content;
	Bunny * bunny;
};

class PluginRss_Player : public QThread
{
	Q_OBJECT

signals:
	void done(bool, Bunny*, QStringList, bool);

public:
	PluginRss_Player(PluginRss * , Bunny *, QStringList, bool);
	virtual ~PluginRss_Player() {}
	void run();

private:
	PluginRss * plugin;
	Bunny * bunny;
	QStringList titles;
	bool save;
};

#endif
