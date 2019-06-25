#ifndef _PLUGINDAYOF_H_
#define _PLUGINDAYOF_H_

#include <QMap>
#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginDayof : public PluginInterface, PluginMessageInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface PluginMessageInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.dayof" )

private slots:
	QString OnApiGet(Bunny *, QVariant);
public:

	PluginDayof();
	virtual ~PluginDayof();

	bool OnClick(Bunny *, PluginInterface::ClickType);
	void OnCron(Bunny *, QVariant, unsigned int);
	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	virtual bool OnRFID(Bunny *, QByteArray const&);
	QString GetVersion() { return "1.0.1"; }

	QStringList GetLanguages() { return QStringList() << "fr" ; }
	QHash<QString, QString> GetExtendedApiFunctions()
	{
		QHash<QString, QString> list;
		list.insert("get", "");
		return list;
	}

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Schedule);
	PLUGIN_BUNNY_API_CALL(Api_Language);

private:
	void sayDayof(Bunny *, bool);
	void sayDayof(Bunny *);
	void InitData();

	QMap<int, QMultiMap<int, QString> > data;
};
#include "plugin_dayof_inline.h"

#endif
