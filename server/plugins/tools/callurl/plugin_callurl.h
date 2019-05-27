#ifndef _PLUGINCALLURL_H_
#define _PLUGINCALLURL_H_

#include <QUrl>
#include <QMultiMap>
#include <QTextStream>
#include "plugininterface.h"

class PluginCallURL : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.tool.callurl" FILE "")

public:
	PluginCallURL();
	virtual ~PluginCallURL();

	bool OnClick(Bunny *, PluginInterface::ClickType);
	bool OnRFID(Bunny * b, QByteArray const& tag);
	void OnCron(Bunny *, QVariant, unsigned int);
	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	void AfterBunnyUnregistered(Bunny *) {};
	void CallURL(Bunny *, QString);
	bool OnEarsMove(Bunny *, int, int);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);

	QString GetVersion() { return "2.0.5"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("2.0.5", "Add supported languages informations");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Url);
	PLUGIN_BUNNY_API_CALL(Api_Schedule);
	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Voice);
	PLUGIN_BUNNY_API_CALL(Api_Ear);
	PLUGIN_API_CALL(Api_Config);
private:
	QString GetURL(Bunny *, QString);
	QString MakeNextName(Bunny *);
};

#endif
