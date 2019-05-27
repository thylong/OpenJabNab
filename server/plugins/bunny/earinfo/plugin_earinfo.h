#ifndef _PLUGINEARINFO_H_
#define _PLUGINEARINFO_H_

#include "plugininterface.h"

class PluginEarinfo : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.earinfo" FILE "")

public:
	PluginEarinfo();
	virtual ~PluginEarinfo();
	QString GetVersion() { return "1.0.1"; }

	bool OnEarsMove(Bunny *, int, int);
};

#endif
