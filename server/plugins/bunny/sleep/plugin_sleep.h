#ifndef _PLUGINSLEEP_H_
#define _PLUGINSLEEP_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>
#include "plugininterface.h"
#include "httprequest.h"
#include "sleeptime.h"

class PluginSleep : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.sleep" )

public slots:
	void OnCronSleep(Bunny *, QVariant, unsigned int);
	void OnCronWakeUp(Bunny *, QVariant, unsigned int);

public:
	PluginSleep();
	virtual ~PluginSleep();
	QString GetVersion() { return "2.1.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.4.3", "Add supported languages informations");
		revisions.insert("2.0.0", "Customizable sleeps");
		revisions.insert("2.1.0", "Add support for Nabaztag V1");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }

	bool OnClick(Bunny *, PluginInterface::ClickType);
	bool OnRFID(Bunny * b, QByteArray const& tag);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	bool OnEarsMove(Bunny *, int, int);
	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	void OnInitPacket(const Bunny * b, AmbientPacket &, SleepPacket &);
	bool NeedToSleep(const Bunny *);

//	QString SpecialBytecode(Bunny *);
	void SetServicesImportant(Bunny *);

	void InitApiCalls();

	PLUGIN_BUNNY_API_CALL(Api_Sleep);
//	PLUGIN_BUNNY_API_CALL(Api_Wakeup);
//	PLUGIN_BUNNY_API_CALL(Api_Setup);
//	PLUGIN_BUNNY_API_CALL(Api_GetSetup);
	PLUGIN_BUNNY_API_CALL(Api_Config);
	PLUGIN_BUNNY_API_CALL(Api_RFID);

private:
	void RegisterCrons(Bunny *);
	void CleanCrons(Bunny *);
	void UpdateState(Bunny *);
	void ConvertConf(Bunny *);

	SleepTime getSleepTime(QString);
	QString fromSleepTime(SleepTime);

	QList<SleepTime> getSleepTimes(QStringList);
	QStringList fromSleepTimes(QList<SleepTime>);

	QList<SleepTime> addSleepTime(QList<SleepTime>, SleepTime);
	QList<SleepTime> compactSleepTime(QList<SleepTime>);
};

Q_DECLARE_METATYPE(SleepTime)
namespace QVariantHelper
{
    inline SleepTime ToSleepTime(QVariant v) { return v.value<SleepTime>(); }
};
#endif
