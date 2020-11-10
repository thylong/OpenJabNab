#ifndef _PluginVolume_H_
#define _PluginVolume_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>
#include "plugininterface.h"
#include "httprequest.h"

class PluginVolume : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.volume" )

private slots:
	QString OnApiGet(Bunny *, QVariant);
	QString OnApiSet(Bunny *, QVariant);
public:
	PluginVolume();
	virtual ~PluginVolume();
	void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &);
	void InitApiCalls();
	virtual void OnCron(Bunny *, QVariant, unsigned int);
	virtual void OnBunnyConnect(Bunny *);
	virtual void OnBunnyDisconnect(Bunny *);
	bool XmppBunnyMessage(Bunny *, QByteArray const&);
	QString GetVersion() { return "1.3.2"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.2.1", "Add supported languages informations");
		revisions.insert("1.2.2", "Add test function");
		revisions.insert("1.3.0", "Add API");
		revisions.insert("1.3.1", "Bug fix in scheduler");
		revisions.insert("1.3.2", "Bug fixes");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }
	QHash<QString, QString> GetExtendedApiFunctions()
	{ 
		QHash<QString, QString> list;
		list.insert("set", "string");
		list.insert("get", "xml");
		return list;
	}

protected:
/*
        PLUGIN_BUNNY_API_CALL(Api_SetSound);
        PLUGIN_BUNNY_API_CALL(Api_GetSound);
        PLUGIN_BUNNY_API_CALL(Api_GetCurrent);
        PLUGIN_BUNNY_API_CALL(Api_PollCurrent);
	PLUGIN_BUNNY_API_CALL(Api_AddChange);
	PLUGIN_BUNNY_API_CALL(Api_RemoveChange);
	PLUGIN_BUNNY_API_CALL(Api_GetChanges);
*/
	PLUGIN_BUNNY_API_CALL(Api_Sound);
	PLUGIN_BUNNY_API_CALL(Api_Schedule);

private:
	void RegisterCrons(Bunny *);
	void CleanCrons(Bunny *);
};

#endif
