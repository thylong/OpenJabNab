#ifndef _PLUGINCOLORBREATHING_H_
#define _PLUGINCOLORBREATHING_H_

#include "plugininterface.h"
#include "httprequest.h"

class PluginColorbreathing : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.colorbreathing" )

private slots:
	QString OnApiColor(Bunny *, QVariant);
	QString OnApiSaveandset(Bunny *, QVariant);

public:
	PluginColorbreathing();
	virtual ~PluginColorbreathing() {};
	void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &);
	void InitApiCalls();
	QStringList GetLanguages() { return QStringList() << "all"; }
	QString GetVersion() { return "2.1.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("2.0.1", "New API to set and save color");
		revisions.insert("2.1.0", "Add support for Nabaztag V1");
		return revisions;
	}
	QHash<QString, QString> GetExtendedApiFunctions()
	{
		QHash<QString, QString> list;
		list.insert("color", "string");
		list.insert("saveandset", "string");
		return list;
	}
//	QString ChooseBytecode(Bunny *);
	void SetServices(Bunny *);

protected:
        PLUGIN_BUNNY_API_CALL(Api_GetColorList);
        PLUGIN_BUNNY_API_CALL(Api_SetColor);
        PLUGIN_BUNNY_API_CALL(Api_GetColor);

        PLUGIN_BUNNY_API_CALL(Api_Color);

	QHash<QString, unsigned char> availableColorsV2;
	QHash<QString, unsigned char> availableColorsV1;
};

#endif
