#ifndef _PLUGINMESSAGES_H_
#define _PLUGINMESSAGES_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"

class PluginMessages : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.messages" )

public slots:
	bool AddMessage(Bunny *, QString, QStringList, int);
	bool AddMessage(Bunny *, QString, QStringList);
	bool RemoveMessage(Bunny *, int);

public:
	PluginMessages();
	virtual ~PluginMessages();

	virtual bool Init();

	void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &);
	virtual bool OnClick(Bunny *, PluginInterface::ClickType);
	virtual void OnCron(Bunny * b, QVariant, unsigned int);
	virtual bool OnRFID(Bunny * b, QByteArray const& tag);
	virtual void OnBunnyConnect(Bunny *);
	virtual void OnBunnyDisconnect(Bunny *);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "1.1.3"; }

	// API
	virtual void InitApiCalls();

	PLUGIN_BUNNY_API_CALL(Api_Schedule);
	PLUGIN_BUNNY_API_CALL(Api_Option);
	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Message);
	PLUGIN_API_CALL(Api_Config);

	QStringList GetLanguages() { return QStringList() << "all"; }

private:
	void PluginStateChanged();
	void sayMessages(Bunny *, QString);
	void sayMessages(Bunny *);

	bool SaveMessages(Bunny *, QStringList);
	int CountMessages(Bunny *);
	int CountMessages(Bunny *, QString);
	void CleanMessages(Bunny *);
	void CleanMessages(Bunny *, int);
	void CleanMessages(Bunny *, QString);
	void CleanMessages(Bunny *, QString, int);
	void ClearMessages(Bunny *);
	QStringList GetMessages(Bunny *);
	int GetKeepTime(Bunny *);
	int GetKeepTime(Bunny *, QString);

	QString getPlugin(QString);

	void MessageNotification(Bunny *);
};
#endif
