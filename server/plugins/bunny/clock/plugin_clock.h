#ifndef _PLUGINCLOCK_H_
#define _PLUGINCLOCK_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"

class PluginClock : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.clock" )

private slots:
	QString OnApiGet(Bunny *, QVariant);

public:
	enum Type { Type_Voice, Type_HourlyBell, Type_SemiHourlyBell, Type_None};
	PluginClock();
	virtual ~PluginClock();
	virtual bool OnClick(Bunny *, PluginInterface::ClickType);
	void OnCron(Bunny*, QVariant, unsigned int);
	bool OnVoiceCommand(Bunny*, QString const&, QStringList const&);
	void OnBunnyConnect(Bunny *);
	void OnBunnyDisconnect(Bunny *);
	QStringList GetLanguages() { return QStringList() << "fr" << "us" << "uk" << "es" << "de" << "ca"; }
	QString GetVersion() { return "1.6.2"; }
        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                list.insert("get", "");
                return list;
        }

	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.6.0", "Add feature to disable hourly clock");
		revisions.insert("1.6.1", "Update plugin for v1 click");
		revisions.insert("1.6.2", "Fix bug for violet voices");
		return revisions;
	}

	//QStringList AdpFileToLoad(Bunny *);

	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Setup);
	PLUGIN_BUNNY_API_CALL(Api_Voice);
	PLUGIN_BUNNY_API_CALL(Api_SetVoice);
	PLUGIN_BUNNY_API_CALL(Api_GetVoiceList);

private:
	bool sayTime(Bunny *);
	QDir clockFolder;
	QMap<Bunny*, QString> bunnyList;
	QStringList availableVoices;
};

#endif
