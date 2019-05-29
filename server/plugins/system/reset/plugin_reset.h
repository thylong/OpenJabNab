#ifndef _PLUGINDEBUG_H_
#define _PLUGINDEBUG_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>
#include "plugininterface.h"
#include "httprequest.h"

class PluginReset : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.system.reset" )
private slots:
	void RemoveReset();
public:
	PluginReset();
	virtual ~PluginReset();
	bool OnClick(Bunny *, PluginInterface::ClickType);
	void InitApiCalls();
	QString GetVersion() { return "0.1.0"; }
	virtual QStringList GetLanguages() { return QStringList() << "fr"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("0.1.0", "Initial version");
		return revisions;
	}

protected:
	PLUGIN_API_CALL(Api_Reset);
private:
	QMap<QByteArray, QDateTime> resettingTime;
	QMap<QByteArray, QString> newAccount;
};

#endif
