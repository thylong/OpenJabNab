#ifndef _PLUGINAUTH_H_
#define _PLUGINAUTH_H_

#include "pluginauthinterface.h"
#include "httprequest.h"

#include <QMap>

class PluginAuth : public PluginAuthInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.system.auth" )

public:
	PluginAuth();
	virtual ~PluginAuth() {};
	bool HttpRequestHandle(HTTPRequest &);
	void OnBunnyConnect(Bunny *);

	virtual bool DoAuth(XmppHandler * xmpp, QByteArray const& data, Bunny ** pBunny, QByteArray & answer);

	QString GetVersion() { return "1.1.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.0.1", "Add reboot bunny on low bootcode");
		revisions.insert("1.0.2", "Add reboot bunny on buggy bootcode");
		revisions.insert("1.0.3", "Update settings instantly");
		revisions.insert("1.1.0", "Identify bunny at first packet");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	void InitApiCalls();
	PLUGIN_API_CALL(Api_Config);
	PLUGIN_API_CALL(Api_SelectAuth);
	PLUGIN_API_CALL(Api_GetListOfAuths);

private:
	int currentId;
	int minBootcode;
	QStringList badBootcodes;
};

#endif
