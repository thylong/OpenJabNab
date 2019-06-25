#ifndef _PLUGINHALLOWEEN_H_
#define _PLUGINHALLOWEEN_H_

#include "plugininterface.h"
	
class PluginHalloween : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.halloween" )
  
private slots:
	QString OnApiSpeak(Bunny *, QVariant);
public:
	PluginHalloween();
	virtual ~PluginHalloween();

	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	virtual void OnCron(Bunny *, QVariant, unsigned int);
	//bool OnRFID(Bunny *, QByteArray const&);
	//bool OnVoiceCommand(Bunny *, QString const&, QStringList const&);
	QString GetVersion() { return "1.0.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.0.0", "Initial version (clone from surprise)");
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
	bool PlaySound(Bunny *);
	void createCron(Bunny *, int, int);
	int GetRandomizedDelay(unsigned int, unsigned int);

	//PLUGIN_BUNNY_API_CALL(Api_RFID);
	PLUGIN_BUNNY_API_CALL(Api_Sound);
	//PLUGIN_BUNNY_API_CALL(Api_Folder);
};

#endif
