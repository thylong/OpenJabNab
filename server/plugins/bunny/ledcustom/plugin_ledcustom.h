#ifndef _PLUGINLEDC_H_
#define _PLUGINLEDC_H_

#include <QMultiMap>
#include <QTextStream>
#include <QDateTime>
#include "plugininterface.h"
#include "ambientpacket.h"

typedef struct {
	int service;
	int value;
	int tempo;
	QString leds;
} Chor;

typedef struct {
	QByteArray bunnyId;
	QDateTime updated;
	QList<Chor> chors;
} BunnyData;

class PluginLedcustom : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.ledcustom" )

signals:
	// weather
	void launchWeatherUpdate(QString);
	// stock
	void launchStockUpdate(QString);

public:
	// global
	PluginLedcustom();
	virtual ~PluginLedcustom();
	void OnBunnyConnect(Bunny *);
	bool XmppBunnyMessage(Bunny *, QByteArray const&);
	QString GetVersion() { return "1.1.2"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.0.0", "Initial version");
		revisions.insert("1.0.1", "Add supported languages and bootcode informations");
		revisions.insert("1.0.2", "Catch debug message");
		revisions.insert("1.1.0", "Add custom choregraphies support");
		revisions.insert("1.1.1", "Add choregraphies fetch from bunny");
		revisions.insert("1.1.2", "Fix ACL bug");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }
	virtual int GetBootcodeRequirement() { return 88; }

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Service);
	PLUGIN_BUNNY_API_CALL(Api_Chor);

private:
	void SendChoregraphiesRequest(Bunny *);
	void updateBunny(Bunny *);
	void updateChoregraphies(Bunny *);

	QMap<QByteArray, BunnyData> services;
};

#endif
