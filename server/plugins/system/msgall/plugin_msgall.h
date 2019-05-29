#ifndef _PLUGINMSGALL_H_
#define _PLUGINMSGALL_H_

#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginMsgall : public PluginInterface, PluginMessageInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface PluginMessageInterface)

    Q_PLUGIN_METADATA(IID "ojn.plugin.system.msgall")

public:
	PluginMsgall();
	virtual ~PluginMsgall();
void InitApiCalls();
	PLUGIN_API_CALL(Api_Say);
};

#endif
