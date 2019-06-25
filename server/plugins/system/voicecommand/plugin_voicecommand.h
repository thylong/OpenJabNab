#ifndef _PLUGINVOICECOMMAND_H_
#define _PLUGINVOICECOMMAND_H_

#include "plugininterface.h"

class QNetworkReply;
#include <QNetworkAccessManager>

class PluginVoiceCommand : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.system.voicecommand" )

signals:
	void deleteHttp();

private slots:
	void recognitionFinished(QNetworkReply* rep);

public:
	PluginVoiceCommand();
	virtual ~PluginVoiceCommand();
	QString GetVersion() { return "1.3.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.3.0", "Add private keys");
		return revisions;
	}

	bool OnRecord(Bunny *, QString const&);

	void InitApiCalls();
	PLUGIN_API_CALL(Api_AddAuthorizedBunny);
	PLUGIN_API_CALL(Api_RemoveAuthorizedBunny);
	PLUGIN_API_CALL(Api_ListAuthorizedBunnies);
	PLUGIN_API_CALL(Api_Key);
	PLUGIN_BUNNY_API_CALL(Api_BunnyKey);
	PLUGIN_API_CALL(Api_Bunny);
	PLUGIN_API_CALL(Api_Language);
	PLUGIN_API_CALL(Api_Words);
	PLUGIN_API_CALL(Api_Sentences);
protected:
private:
  QNetworkAccessManager http;
	QString cleanString(QString);
	QString makeLanguage(QString);
	bool saveWords(Bunny *, QString);
	void analyzeWords(Bunny *, QString, bool);

	bool OnVoiceBeforeBunny(QString const&, QString const&);
	bool OnVoiceAfterBunny(QString const&, QString const&);
};
#endif
