#ifndef _PLUGINNEEDTOKNOW_H_
#define _PLUGINNEEDTOKNOW_H_

#include <QUrl>
#include <QNetworkAccessManager>
#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginNeedtoknow : public PluginInterface, PluginMessageInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface PluginMessageInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.services.needtoknow" )

private slots:
	QString OnApiGet(Bunny *, QVariant);
	void analyseHtml(QNetworkReply*);
	void analyseDone(bool, Bunny*, QStringList, bool);

public:
	PluginNeedtoknow();
	virtual ~PluginNeedtoknow();
	bool OnClick(Bunny *, PluginInterface::ClickType);
	bool OnRFID(Bunny * b, QByteArray const& tag);
	void OnCron(Bunny *, QVariant, unsigned int);
	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	void AfterBunnyUnregistered(Bunny *) {};
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "1.0.3"; }
	QStringList GetLanguages() { return QStringList() << "fr"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.0.0", "Initial release");
		revisions.insert("1.0.1", "Add some API");
		revisions.insert("1.0.2", "Bug fix in API");
		revisions.insert("1.0.3", "Fix encoding and remove details");
		return revisions;
	}
        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                list.insert("get", "");
                return list;
        }


	void InitApiCalls();
	PLUGIN_API_CALL(Api_Language);
	PLUGIN_BUNNY_API_CALL(Api_Schedule);
	PLUGIN_BUNNY_API_CALL(Api_RFID);
private:
	void createCron(Bunny *, int, QString);
	int GetRandomizedDelay(unsigned int);
	void getNTKPage(Bunny *, QString, bool);
	void getNTKPage(Bunny *, QString);
	void getNTKPage(Bunny *);
};

class PluginNTK_WORKER : public QThread
{
	Q_OBJECT

signals:
	void done(bool, Bunny*, QStringList, bool);

public:
	PluginNTK_WORKER(PluginNeedtoknow * ,Bunny * ,QString ,QString , bool);
	virtual ~PluginNTK_WORKER() {}
	void run();

private:
	PluginNeedtoknow * plugin;
	Bunny * bunny;
	QString language;
	QString buffer;
	bool save;
};
#endif
