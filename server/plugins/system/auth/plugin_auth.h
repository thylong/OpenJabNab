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

	virtual const QString GetVersion(void) override { return "1.1.0"; }
	virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
	virtual const QHash<QString, QString> GetChangelog(void) override;

private:
	// API
	virtual void InitApiCalls(void) override;
	PLUGIN_API_CALL(Api_Config);
	PLUGIN_API_CALL(Api_SelectAuth);
	PLUGIN_API_CALL(Api_GetListOfAuths);

	int currentId;
	int minBootcode;
	QStringList badBootcodes;
};

#endif
