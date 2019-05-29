#ifndef _PLUGINDICE_H_
#define _PLUGINDICE_H_

#include "plugininterface.h"

class PluginDice : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.user.dice" )

public:
	PluginDice();
	virtual ~PluginDice();
	bool OnClick(Bunny *, PluginInterface::ClickType);
	QString GetVersion() { return "1.2.0"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.2.0", "Add support for Nabaztag V1");
		return revisions;
	}

	QStringList AdpFileToLoad(Bunny *);

private:
	QMap<Bunny *, QStringList> soundToSend;
};

#endif
