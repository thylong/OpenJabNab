#ifndef _ZTAMP_H_
#define _ZTAMP_H_

#include <QByteArray>
#include <QHash>
#include <QString>
#include <QVariant>
#include "apihandler.h"

#include "global.h"
#include "packet.h"
#include "plugininterface.h"

//class XmppHandler;
class OJN_EXPORT Ztamp 
	: public ApiHandler<Ztamp>
{
	friend class ZtampManager;
public:
	virtual ~Ztamp();

	static void Init() { InitApiCalls(); }

	QByteArray GetID() const;
	QString GetZtampName() const;
	void SetZtampName(QString const& ztampName);

	QVariant GetPluginSetting(QString const&, QString const&, QVariant const& defaultValue = QVariant()) const;
	void SetPluginSetting(QString const&, QString const&, QVariant const&);
	void RemovePluginSetting(QString const&, QString const&);

	bool HasPlugin(PluginInterface * p) const;
	QList<QString> GetListOfPlugins();

	bool OnRFID(Bunny *);

	void PluginStateChanged(PluginInterface * p);
	void PluginLoaded(PluginInterface *);
	void PluginUnloaded(PluginInterface *);

	QVariant GetGlobalSetting(QString const&, QVariant const& defaultValue = QVariant()) const;
	void SetGlobalSetting(QString const&, QVariant const&);
	void RemoveGlobalSetting(QString const&);

	QMap<QString, QVariant> Associations();
	QString Association(Bunny *);
	bool Associate(Bunny *, PluginInterface *);
	bool Associate(Bunny *, QString);
	bool Associate(QString, PluginInterface *);
	bool Associate(QString, QString);
	bool Dissociate(Bunny *);
	bool Dissociate(QString);

	// API
	static void InitApiCalls();

private slots:
	void SaveConfig();

private:
	Ztamp(QByteArray const&);
	void LoadConfig();
	void AddPlugin(PluginInterface * p);
	void RemovePlugin(PluginInterface * p);
	void OnConnect();
	void OnDisconnect();

	QString CheckPlugin(PluginInterface *, bool isAssociated = false);

	// API
	API_CALL(Api_AddPlugin);
	API_CALL(Api_RemovePlugin);
	API_CALL(Api_GetListOfAssociatedPlugins);
	API_CALL(Api_SetZtampName);
	API_CALL(Api_RemoveOwner);
	API_CALL(Api_ResetOwner);

	API_CALL(Api_Owner);
	API_CALL(Api_Config);
	API_CALL(Api_Plugin);

	QByteArray id;
	QString configFileName;
	QHash<QString, QVariant> GlobalSettings;
	QHash<QString, QHash<QString, QVariant> > PluginsSettings;
	QList<QString> listOfPlugins;
	QList<PluginInterface*> listOfPluginsPtr;
	bool needSave;

	// RFID Tags
};

inline QList<QString> Ztamp::GetListOfPlugins()
{
	return listOfPlugins;
}

inline QByteArray Ztamp::GetID() const
{
	return id.toHex();
}

inline QString Ztamp::GetZtampName() const
{
	return GetGlobalSetting("ZtampName", "Ztamp").toString();
}

inline void Ztamp::SetZtampName(QString const& ztampName)
{
	SetGlobalSetting("ZtampName", ztampName);
}

inline bool Ztamp::HasPlugin(PluginInterface * p) const
{
	return listOfPluginsPtr.contains(p);
}

Q_DECLARE_METATYPE(Ztamp*)
namespace QVariantHelper
{
    inline Ztamp* ToZtampPtr(QVariant v) { return v.value<Ztamp*>(); }
};
#endif
