#ifndef _PLUGININTERFACE_H_
#define _PLUGININTERFACE_H_

#include <QByteArray>
#include <QCoreApplication>
#include <QDir>
#include <QSettings>
#include <QString>
#include <QtPlugin>
#include "apimanager.h"
#include "bunnymanager.h"
#include "ztampmanager.h"
//#include "debuglog.h"
#include "log.h"
#include "QsLog.h"
#include "pluginapihandler.h"
#include "settings.h"
#include "ttsanswer.h"

class Account; 
class AmbientPacket;
class Bunny;
class HTTPRequest;
class Packet;
class MessagePacket;
class SleepPacket;

class PluginInterface : public QObject, public PluginApiHandler
{
	friend class PluginManager;
public:
	enum ClickType { SingleClick = 0, DoubleClick};
	enum PluginType {
		RequiredPlugin 		= 0b1,
		SystemPlugin 		= 0b10,
		SystemAfterPlugin 	= 0b100,
		BunnyV1Plugin 		= 0b1000, // Nabaztag
		BunnyV2Plugin 		= 0b10000, // Nabaztag:tag
		BunnyV3Plugin 		= 0b100000, // Karotz
		RfidPlugin 		= 0b1000000, // plugin for bunny, with RFID actions
		ZtampPlugin 		= 0b10000000, // plugin for ztamp
		SingleClickPlugin 	= 0b100000000,
		DoubleClickPlugin 	= 0b1000000000,
		CronPlugin 		= 0b10000000000,
		EarsPlugin 		= 0b100000000000,
		ListenPlugin 		= 0b1000000000000,
		PeriodPlugin 		= 0b10000000000000,
		ApiPlugin 		= 0b100000000000000,
		RecordPlugin 		= 0b1000000000000000,
		VoicePlugin 		= 0b10000000000000000,
		PremiumPlugin 		= 0b100000000000000000,
		DevPlugin 		= 0b1000000000000000000,
		MessagePlugin 		= 0b10000000000000000000
	};

	PluginInterface(QString name, QString visualName = QString(), int type = 0);
	virtual ~PluginInterface();
	
	// Called to init plugin, return false if something is wrong
	virtual bool Init() { return true; };

	virtual void HttpRequestBefore(HTTPRequest &) {}
	// If the plugin returns true, the plugin should handle the request
	virtual bool HttpRequestHandle(HTTPRequest &) { return false; }
	virtual void HttpRequestAfter(HTTPRequest &) { }
	
	// Raw XMPP Messages
	virtual bool XmppBunnyMessage(Bunny *, QByteArray const&) { return false; }
	virtual void BeforeSendMessage(Bunny *, MessagePacket *, QString) { }

	// Bunny's Messages
	virtual void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &) {}
	virtual bool OnClick(Bunny *, ClickType) { return false; }
	virtual bool OnEarsMove(Bunny *, int, int) { return false; }
	virtual bool OnListen(Bunny *, int) { return false; }
	virtual bool OnRecord(Bunny *, QString const&) { return false; }
	virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) { return false; }
	virtual bool OnRFID(Bunny *, QByteArray const&) { return false; }
	virtual bool OnRFID(Ztamp *, Bunny *) { return false; }

	// V1
	virtual void SetServices(Bunny *) { }
	virtual void SetServicesImportant(Bunny *) { }
	virtual QString SpecialBytecode(Bunny *) { return QString(); }
	virtual QString ChooseBytecode(Bunny *) { return QString(); }
	virtual QByteArray AddData(Bunny *) { return QByteArray(); }
	virtual QStringList AdpFileToLoad(Bunny * b);
	virtual QByteArray BytecodeToSend(Bunny *) { return QByteArray(); }

	// Cron system
	virtual void OnCron(Bunny*, QVariant, unsigned int type = 0) { Q_UNUSED(type); }

	// Ztamp connect/disconnect
	virtual void OnZtampConnect(Ztamp *) {}
	virtual void OnZtampDisconnect(Ztamp *) {}
	
	// Bunny connect/disconnect
	virtual void OnBunnyConnect(Bunny *) {}
	virtual void OnBunnyDisconnect(Bunny *) {}
	
	// Settings
	QVariant GetSettings(QString const& key, QVariant const& defaultValue = QVariant()) const;
	void SetSettings(QString const& key, QVariant const& value);
	void RemoveSettings(QString const& key);
	void SetSettings(QString const& key, QVariant const& value, bool const& sync);
	void RemoveSettings(QString const& key, bool const& sync);
	void SyncSettings();

	// Plugin's name
	QString const& GetName() const;
	QString const& GetVisualName() const;

	// Plugin enable/disable functions
	bool GetEnable() const;

	// Plugin type
	int GetType() const;
	virtual QStringList GetLanguages() { return QStringList() << "fr"; }
	virtual QString GetVersion() { return "0.0.0"; }
	virtual int GetBootcodeRequirement() { return 0; }
	virtual QHash<QString, QString> GetChangelog() { return QHash<QString, QString>(); }

	// Voice commands
	virtual QHash<QString, QString> GetVoiceCommands(QString) { return QHash<QString, QString>(); }

	// Extended Violet API
	bool SupportExtendedApi() const;
	virtual QHash<QString, QString> GetExtendedApiFunctions() { return QHash<QString, QString>(); }

	bool GetTTSLog() const;
	void SetTTSLog(bool b);
	virtual void TTSLog(QByteArray const& bunny, QString const& what, TTSAnswer answer);
	virtual void TTSLog(QString const& bunny, QString const& what, TTSAnswer answer);

	virtual void PluginDebug(QString s) { PluginDebug(s, 1); }
	virtual void PluginDebug(QString s, int l) { if(logLevel >= l) LogInfo(s); }

protected:
	void SetEnable(bool);
	virtual void PluginStateChanged() {}
	QDir * GetLocalHTTPFolder() const;
	QByteArray GetBroadcastHTTPPath(QString f) const;
	QByteArray GetFullHTTPPath(QString f) const;

	float getPertinence(QString, QString);
	virtual QString cleanStringPertinence(QString);
	virtual QStringList cleanStringListPertinence(QStringList);
	void AddSoundToSend(Bunny *, QString);

	QSettings * settings;
	int logLevel;
	QMap<Bunny *, QStringList> soundToSend;

private:
	QString pluginName;
	int pluginType;
	QString pluginVisualName;
	bool pluginEnable;
	bool pluginTTSLog;
	QString httpFolder;
};

#include "plugininterface_inline.h"

Q_DECLARE_INTERFACE(PluginInterface,"org.toms.openjabnab.PluginInterface/1.0")

#endif
