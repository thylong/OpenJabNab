#ifndef _PLUGINMUSIC_H_
#define _PLUGINMUSIC_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"

class PluginMusic : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.user.music" FILE "")

public:
	enum LibraryMode { NoLibrary, MixedLibrary = 0b1, OwnLibrary = 0b10, SharedLibrary = 0b100, PrivateLibrary = 0b1000 };
	PluginMusic();
	virtual ~PluginMusic() {}
	virtual bool Init();
	virtual bool OnRFID(Bunny *, QByteArray const&);
	virtual bool OnRFID(Ztamp *, Bunny *);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "2.0.1"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("2.0.1", "Fix bug for mp3 file listing");
		return revisions;
	}

	bool OnClick(Bunny *, PluginInterface::ClickType);

	// API
	void InitApiCalls();
/*
	PLUGIN_BUNNY_API_CALL(Api_Play);
	PLUGIN_BUNNY_API_CALL(Api_AddRFID);
	PLUGIN_BUNNY_API_CALL(Api_RemoveRFID);
	PLUGIN_BUNNY_API_CALL(Api_ListRFID);
	PLUGIN_BUNNY_API_CALL(Api_getFilesList);
	PLUGIN_BUNNY_API_CALL(Api_libraryMode);
*/
	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_File);
	PLUGIN_BUNNY_API_CALL(Api_Library);
private:
	QByteArray GetBroadcastHTTPUserPath(Bunny *, QString);
	bool playFile(Bunny *, QString);
	bool playRandomFile(Bunny *);
	bool playRandomInGroup(Bunny *, QString);
	QDir * GetUserDir(Bunny *);
	QDir musicFolder;
	QDir userFolder;

};
#endif
