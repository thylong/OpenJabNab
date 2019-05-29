#ifndef _PLUGINMEMO_H_
#define _PLUGINMEMO_H_

#include <QMultiMap>
#include "plugininterface.h"

class PluginMemo : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.user.memo" )

public:
	PluginMemo();
	virtual ~PluginMemo();
	QString GetVersion() { return "2.0.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.9.4", "Add supported languages informations");
		revisions.insert("2.0.0", "Add support for Nabaztag V1");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	void OnCron(Bunny *, QVariant, unsigned int);
	void AfterBunnyUnregistered(Bunny *) {};

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Schedule);

private:
	QDir memoFolder;
};

#endif
