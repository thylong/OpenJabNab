#ifndef _PLUGINLISTEN_H_
#define _PLUGINLISTEN_H_

#include <QDateTime>
#include <QMap>
#include "plugininterface.h"

typedef struct {
	Bunny * bunny;
	unsigned int volume;
	QDateTime date;
} ListenElement;

class PluginListen : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)

    Q_PLUGIN_METADATA(IID "ojn.plugin.system.listen" )

public:
	PluginListen();
	virtual ~PluginListen();

	bool XmppBunnyMessage(Bunny *, QByteArray const&);
	void OnBunnyConnect(Bunny *);
	void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &);
	void SendListeningGain(Bunny *);
	QStringList GetLanguages() { return QStringList() << "all"; }
	QString GetVersion() { return "1.0.0"; }

	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Config);

private:
	QMap<QByteArray, ListenElement> listenList;
};

#endif
