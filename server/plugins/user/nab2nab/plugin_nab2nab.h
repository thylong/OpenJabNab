#ifndef _PLUGINNAB2NAB_H_
#define _PLUGINNAB2NAB_H_

#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginNab2nab : public PluginInterface, PluginMessageInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface PluginMessageInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.user.nab2nab" )

public:
	PluginNab2nab();
	virtual ~PluginNab2nab();

	enum NabAnnounce { None, Repeat, Always };

	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	QString GetVersion() { return "1.2.2"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.2.2", "Add supported languages informations");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	bool OnRFID(Bunny *, QByteArray const&);
	bool OnRecord(Bunny *, QString const&);
	void SendAudio(QString url);
	void SendMessage(QString text);

	void InitApiCalls();

	PLUGIN_BUNNY_API_CALL(Api_SetPreference);
	PLUGIN_BUNNY_API_CALL(Api_GetPreference);
	PLUGIN_BUNNY_API_CALL(Api_AddFavorite);
	PLUGIN_BUNNY_API_CALL(Api_RemoveFavorite);
	PLUGIN_BUNNY_API_CALL(Api_GetFavorites);
	PLUGIN_BUNNY_API_CALL(Api_SetReceiver);
	PLUGIN_BUNNY_API_CALL(Api_RemoveReceiver);
	PLUGIN_BUNNY_API_CALL(Api_GetReceivers);

	PLUGIN_BUNNY_API_CALL(Api_Config);
	PLUGIN_BUNNY_API_CALL(Api_Friend);
/*
	PLUGIN_API_CALL(Api_SendMessage);
	PLUGIN_API_CALL(Api_SendAudio);
	PLUGIN_API_CALL(Api_ReceiveMessage);
*/
private:
	QByteArray GetRecordBroadcastHTTPPath(QString f) const;
	QStringList announceText;
};

#endif
