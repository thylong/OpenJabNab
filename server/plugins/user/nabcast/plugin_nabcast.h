#ifndef _PLUGINNABCAST_H_
#define _PLUGINNABCAST_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"

class PluginNabcast : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
	Q_PLUGIN_METADATA(IID "ojn.plugin.user.nabcast" FILE "")

public:
	PluginNabcast();
	virtual ~PluginNabcast() {}
	virtual bool Init();
	virtual bool OnRFID(Bunny *, QByteArray const&);
	virtual bool OnRFID(Ztamp *, Bunny *);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "1.0.1"; }
	QStringList GetLanguages() { return QStringList() << "all"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.0.0", "Initial version");
		revisions.insert("1.0.1", "Fix bug for mp3 file listing");
		return revisions;
	}
        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                //list.insert("get", "");
                return list;
        }

	bool OnClick(Bunny *, PluginInterface::ClickType);

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Schedule);
	PLUGIN_BUNNY_API_CALL(Api_Register);
	PLUGIN_BUNNY_API_CALL(Api_Nabcast);
	PLUGIN_BUNNY_API_CALL(Api_File);
	PLUGIN_BUNNY_API_CALL(Api_Library);
private:
	QByteArray GetBroadcastHTTPUserPath(Bunny *, QString);
	bool playFile(Bunny *, QString);
	bool playRandomFile(Bunny *);
	QDir * GetUserDir(Bunny *);
	QDir nabcastFolder;
	QDir userFolder;

};
#endif
