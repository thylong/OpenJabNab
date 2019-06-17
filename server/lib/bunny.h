#ifndef _BUNNY_H_
#define _BUNNY_H_

#include <QByteArray>
#include <QDateTime>
#include <QHash>
#include <QString>
#include <QTimer>
#include <QVariant>
#include <QRegExp>
#include "apihandler.h"
#include "apimanager.h"
#include "global.h"
#include "packet.h"
//#include "accountmanager.h"
#include "plugininterface.h"

class XmppHandler;
class OJN_EXPORT Bunny : public QObject, public ApiHandler<Bunny>
{
	friend class BunnyManager;
	Q_OBJECT
public:
	enum State { State_Disconnected, State_Authenticating, State_Authenticated, State_Ready};
	enum Services { ServiceNone = 0, ColorBreathing = 9, LeftEar = 16, RightEar = 17, Nose = 18 };
	virtual ~Bunny();

	static void Init() { InitApiCalls(); }

	QByteArray GetID() const;
	void SetXmppHandler (XmppHandler *);
	void RemoveXmppHandler (XmppHandler *);
	void SendPacket(Packet const&, QString);
	void SendPacket(Packet const&);
	void SendData(QByteArray const&);
	void SendExpertData(QByteArray const&);

	QString GetBunnyName() const;
	void SetBunnyName(QString const& bunnyName);
	QString GetLanguage() const;
	void SetLanguage(QString const& lng);
	QString GetVoice() const;
	void SetVoice(QString const& tts, QString const& voice);
	int GetVersion() const;
	void SetVersion(int const&);
	QString GetBootcode() const;
	int GetBootcodeBase() const;
	void SetBootcode(QString const&);
	bool IsBootcodeCompatible(PluginInterface *);
	QByteArray GetBunnyPassword() const;
	bool SetBunnyPassword(QByteArray const& bunnyName);
	bool ClearBunnyPassword();

	QByteArray GetXmppResource() const;
	void SetXmppResource(QByteArray const&);

	QVariant GetPluginSetting(QString const&, QString const&, QVariant const& defaultValue = QVariant()) const;
	QStringList GetPluginSettings(QString const&) const;
	void SetPluginSetting(QString const&, QString const&, QVariant const&);
	void RemovePluginSetting(QString const&, QString const&);

	QHash<QString, QVariant> ExportPluginSettings(QString const&);
	void ImportPluginSettings(QString const&, QHash<QString, QVariant>);

	QVariant GetGlobalSetting(QString const&, QVariant const& defaultValue = QVariant()) const;
	void SetGlobalSetting(QString const&, QVariant const&);
	void RemoveGlobalSetting(QString const&);
	void CleanSettings();

	bool HasPlugin(PluginInterface * p) const;
	QList<QString> GetListOfPlugins();

	bool XmppBunnyMessage(QByteArray const&);

	void Authenticating();
	void Authenticated();
	void Ready();

	bool IsAuthenticated() const;
	bool IsConnected() const;

	bool IsLimited() const;

	bool IsIdle() const;
	bool IsSleeping() const;

	QByteArray GetInitPacket() const;

	bool OnClick(PluginInterface::ClickType);
	bool OnEarsMove(int, int);
	bool OnListen(int);
	bool OnRFID(QByteArray const&);
	bool OnRecord(QString const&);
	bool OnVoiceCommand(QString const&, QStringList const&);

	void PluginStateChanged(PluginInterface * p);
	void PluginLoaded(PluginInterface *);
	void PluginUnloaded(PluginInterface *);

	QDir * GetUserDir();
	QByteArray GetBroadcastHTTPUserPath(QString);

	void AddPlugin(PluginInterface * p);
	void RemovePlugin(PluginInterface * p);

	// Services
	void UpdateServices();
	void UpdateServicesImportant();
	void SetService(int, int);
	int GetService(int);
	QMap<int, int> GetServices();

	int GetLeftEar();
	void SetLeftEar(int);
	int GetRightEar();
	void SetRightEar(int);

	// V1
	QStringList AdpFileToLoad();
	QString ChooseBytecode();
	QString SpecialBytecode();
	QByteArray AddData();

	void AddSoundToSend(QString);
	void ClearSoundToSend();

	// Secure
	int GetApiChorCount();
	void AddApiChor();
	int GetApiSpecialCount();
	void AddApiSpecial();

	// API
	static void InitApiCalls();
	ApiManager::ApiAnswer * ProcessVioletApiCall(HTTPRequest const&);

	void AddInXmppTraffic(int);
	void AddInHttpTraffic(int);
	void AddOutXmppTraffic(int);
	void AddOutHttpTraffic(int);

	void ResetInXmppTraffic();
	void ResetInHttpTraffic();
	void ResetOutXmppTraffic();
	void ResetOutHttpTraffic();

	void OnNewPing();
	void OnNoPing();

private slots:
	void SaveConfig();

private:
	Bunny(QByteArray const&);
	void LoadConfig();
	void OnConnect();
	void OnDisconnect();

	QString GetXmlVoiceList(QString);
	QString GetXmlVoiceList();
	QString CheckPlugin(PluginInterface *, bool isAssociated = false);

	// API
	API_CALL(Api_AddPlugin);
	API_CALL(Api_RemovePlugin);
	API_CALL(Api_GetTimeZone);
	API_CALL(Api_SetTimeZone);
	API_CALL(Api_GetListOfAssociatedPlugins);
	API_CALL(Api_SetSingleClickPlugin);
	API_CALL(Api_SetDoubleClickPlugin);
	API_CALL(Api_GetClickPlugins);
	API_CALL(Api_GetListOfKnownRFIDTags);
	API_CALL(Api_SetRFIDTagName);
	API_CALL(Api_SetBunnyName);
	API_CALL(Api_SetService);
	API_CALL(Api_ResetPassword);
	API_CALL(Api_ResetOwner);
	API_CALL(Api_GetOwner);
	API_CALL(Api_Disconnect);
  API_CALL(Api_setPublicVApi);
  API_CALL(Api_getPublicVApi);
	API_CALL(Api_enableVApi);
	API_CALL(Api_disableVApi);
	API_CALL(Api_getVApiStatus);
	API_CALL(Api_getInsomniac);
	API_CALL(Api_setInsomniac);
	API_CALL(Api_getVApiToken);
	API_CALL(Api_setVApiToken);
	API_CALL(Api_getAllLast);
	API_CALL(Api_getOneLast);
	API_CALL(Api_getVersion);
	API_CALL(Api_getBootcode);
	API_CALL(Api_setVersion);
	API_CALL(Api_getLanguage);
	API_CALL(Api_setLanguage);
	API_CALL(Api_getAllCronList);
	API_CALL(Api_getNextCronList);
	API_CALL(Api_Resource);
	API_CALL(Api_Traffic);

  API_CALL(Api_DeletePluginSettings);

	//API_CALL(Api_Language);
	API_CALL(Api_Voice);

	enum State state;

	QByteArray id;
	QByteArray xmppResource;
	//QString configFileName;
	QHash<QString, QVariant> GlobalSettings;
	QHash<QString, QHash<QString, QVariant> > PluginsSettings;
	QList<QString> listOfPlugins;
	QList<PluginInterface*> listOfPluginsPtr;
	QTimer * saveTimer;
	XmppHandler * xmppHandler;
	bool needSave;

	PluginInterface * singleClickPlugin;
	PluginInterface * doubleClickPlugin;

	int apiSpecialCount;
	QDateTime lastApiSpecialCount;
	int apiChorCount;
	QDateTime lastApiChorCount;

	QString lastTTS;
	QDateTime lastTTSTime;


	// RFID Tags
	QHash<QByteArray, QString> knownRFIDTags;

	//
	QStringList messages;
	QMap<int, int> services;
	int leftEar;
	int rightEar;
	QStringList soundToSend;

	// Traffic
	int trafficCount;
	unsigned long long inHttpTraffic;
	unsigned long long outHttpTraffic;
	unsigned long long inXmppTraffic;
	unsigned long long outXmppTraffic;
};

inline void Bunny::AddApiSpecial()
{
	lastApiSpecialCount = QDateTime::currentDateTime();
	apiSpecialCount++;
}

inline int Bunny::GetApiSpecialCount()
{
	if(lastApiSpecialCount.date().dayOfYear() != QDate::currentDate().dayOfYear())
	{
		apiSpecialCount = 0;
	}
	return apiSpecialCount;
}

inline void Bunny::AddApiChor()
{
	lastApiChorCount = QDateTime::currentDateTime();
	apiChorCount++;
}

inline int Bunny::GetApiChorCount()
{
	if(lastApiChorCount.date().dayOfYear() != QDate::currentDate().dayOfYear())
	{
		apiChorCount = 0;
	}
	return apiChorCount;
}

inline QList<QString> Bunny::GetListOfPlugins()
{
	return listOfPlugins;
}

inline bool Bunny::IsIdle() const
{
	if(GetVersion() == 1)
		return IsConnected() && !(GetGlobalSetting("asleep", false).toBool());
	return IsConnected() && ((bool)(xmppResource == "idle"));
}

inline bool Bunny::IsSleeping() const
{
	if(GetVersion() == 1)
		return IsConnected() && (GetGlobalSetting("asleep", false).toBool());
	return IsConnected() && ((bool)(xmppResource == "asleep"));
}

inline bool Bunny::IsConnected() const
{
	if(GetVersion() == 2 || GetVersion() == 3)
		return state == State_Ready;
 	return GetGlobalSetting("Last Ping", QString("")).toDateTime().secsTo(QDateTime::currentDateTime()) < 60 * 20;
}

inline bool Bunny::IsAuthenticated() const
{
	return (state == State_Ready) || (state == State_Authenticated);
}

inline QByteArray Bunny::GetID() const
{
	return id.toHex();
}

inline QByteArray Bunny::GetXmppResource() const
{
	return xmppResource;
}

inline void Bunny::SetXmppResource(QByteArray const& r)
{
	xmppResource = r;
}

inline QString Bunny::GetBunnyName() const
{
	return GetGlobalSetting("BunnyName", "Nabaztag" + QString(GetID()).right(2).toUpper()).toString();
}

inline void Bunny::SetBunnyName(QString const& bunnyName)
{
	SetGlobalSetting("BunnyName", bunnyName);
}

inline QString Bunny::GetLanguage() const
{
	return GetGlobalSetting("Language", GlobalSettings::Get("Language/Default", "en").toString()).toString();
	//Account * a = AccountManager::GetAccountByLogin(GetGlobalSetting("OwnerAccount").toByteArray());
	//return GetGlobalSetting("Language", a->GetLanguage()).toString();
}

inline void Bunny::SetLanguage(QString const& lng)
{
	SetGlobalSetting("Language", lng);
}

inline QString Bunny::GetVoice() const
{
	QString voice = GetGlobalSetting("Voice", QString()).toString();
	return voice.length() > 0 ? voice : "nabalive/fr";
}

inline void Bunny::SetVoice(QString const& tts, QString const& voice)
{
	SetGlobalSetting("Voice", tts + "/" + voice);
}

inline QString Bunny::GetBootcode() const
{
	return GetGlobalSetting("Bootcode", "OJN01").toString();
}

inline void Bunny::SetBootcode(QString const& v)
{
	SetGlobalSetting("Bootcode", v);
}

inline int Bunny::GetBootcodeBase() const
{
	QRegExp rx("^OJN(\\d+)[a-z]*$");
	if(rx.indexIn(GetBootcode()) != -1)
	{
		return rx.cap(1).trimmed().toInt();
	}
	return 0;
}

inline bool Bunny::IsBootcodeCompatible(PluginInterface * p)
{
	int needed = p->GetBootcodeRequirement();
	if(needed > 0)
	{
		if(GetBootcodeBase() >= needed)
		{
			return true;
		}
		return false;
	}
	return true;
}

inline int Bunny::GetVersion() const
{
	return GetGlobalSetting("Version", 2).toInt();
}

inline void Bunny::SetVersion(int const& v)
{
	SetGlobalSetting("Version", v);
}

inline QByteArray Bunny::GetBunnyPassword() const
{
	return GetGlobalSetting("BunnyPassword", QByteArray()).toByteArray();
}

inline bool Bunny::ClearBunnyPassword()
{
	RemoveGlobalSetting("BunnyPassword");
	return true;
}
inline bool Bunny::SetBunnyPassword(QByteArray const& bunnyPassword)
{
	if(Bunny::GetBunnyPassword() == QByteArray())
	{
		SetGlobalSetting("BunnyPassword", bunnyPassword);
		return true;
	}
	LogError(QString("Forcing new password set for bunny : ").append(QString(GetID())));
	SetGlobalSetting("BunnyPassword", bunnyPassword);
	return true;
}

inline bool Bunny::HasPlugin(PluginInterface * p) const
{
	if(listOfPluginsPtr.count())
		return listOfPluginsPtr.contains(p);
	return false;
}

inline int Bunny::GetLeftEar()
{
	return leftEar;
}

inline void Bunny::SetLeftEar(int ear)
{
	leftEar = ear;
}

inline int Bunny::GetRightEar()
{
	return rightEar;
}

inline void Bunny::SetRightEar(int ear)
{
	rightEar = ear;
}
Q_DECLARE_METATYPE(Bunny*)
namespace QVariantHelper
{
    inline Bunny* ToBunnyPtr(QVariant v) { return v.value<Bunny*>(); }
};
#endif
