#ifndef _PLUGINMEMO_H_
#define _PLUGINMEMO_H_

#include "plugininterface.h"

class PluginMemo
	: public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
	Q_PLUGIN_METADATA(IID "ojn.plugin.user.memo" )

public:
	PluginMemo();

	virtual void OnBunnyConnect(Bunny *) override;
	virtual void OnBunnyDisconnect(Bunny *) override;
	virtual void OnCron(Bunny *, QVariant, unsigned int) override;

	virtual const QString GetVersion(void) override { return "2.0.0"; }
	virtual const QHash<QString, QString> GetChangelog(void) override
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.9.4", "Add supported languages informations");
		revisions.insert("2.0.0", "Add support for Nabaztag V1");
		return revisions;
	}
	virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }


private:
	virtual ~PluginMemo();

	QDir memoFolder;

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Schedule);
};

#endif
