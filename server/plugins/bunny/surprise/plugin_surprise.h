#ifndef _PLUGINSURPRISE_H_
#define _PLUGINSURPRISE_H_

#include "plugininterface.h"

class PluginSurprise : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.surprise" FILE "")

private slots:
	QString OnApiSpeak(Bunny *, QVariant);
public:
	PluginSurprise();
	virtual ~PluginSurprise();

	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	virtual void OnCron(Bunny *, QVariant, unsigned int);
	bool OnRFID(Bunny *, QByteArray const&);
	bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "2.4.1"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("2.3.0", "Rename plugin to avoid mistakes");
		revisions.insert("2.3.1", "Insert a minimum time to avoid crash");
		revisions.insert("2.4.0", "Add support for Nabaztag V1");
		revisions.insert("2.4.1", "Fix bug for mp3 file listing");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "fr" << "en" << "es" << "it" << "de"; }


	void InitApiCalls();
        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                list.insert("speak", "");
                return list;
        }


protected:
	bool PlaySurprise(Bunny *, QString);
	void createCrons(Bunny *);
	void createCron(Bunny *, int, QString);
	int GetRandomizedFrequency(unsigned int);

	PLUGIN_BUNNY_API_CALL(Api_GetFolderList);
	PLUGIN_BUNNY_API_CALL(Api_SetSurprise);
	PLUGIN_BUNNY_API_CALL(Api_GetSurprises);
	PLUGIN_BUNNY_API_CALL(Api_DelSurprise);

	PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Surprise);
	PLUGIN_BUNNY_API_CALL(Api_Folder);

	QStringList availableSurprises;
};

#endif
