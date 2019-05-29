#ifndef _PLUGINTAICHI_H_
#define _PLUGINTAICHI_H_

#include "plugininterface.h"

class PluginTaichi : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.taichi" )

private slots:
	QString OnApiTaichi(Bunny *, QVariant);
public:
	PluginTaichi();
	virtual ~PluginTaichi();

	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &);
	void SendTaichiFrequency(Bunny *);
	bool OnRFID(Bunny * b, QByteArray const& tag);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QStringList GetLanguages() { return QStringList() << "all"; }
	QString GetVersion() { return "1.2.1"; }

	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_SetFrequency);
	PLUGIN_BUNNY_API_CALL(Api_GetFrequency);
	PLUGIN_BUNNY_API_CALL(Api_SetRFID);

        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                list.insert("taichi", "");
                return list;
        }
};

#endif
