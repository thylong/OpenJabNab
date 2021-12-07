#ifndef _PLUGINMSGALL_H_
#define _PLUGINMSGALL_H_

#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginMsgall
  : public PluginInterface
  , private PluginMessageInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface PluginMessageInterface)

  Q_PLUGIN_METADATA(IID "ojn.plugin.system.msgall")

public:
  PluginMsgall();

private:
  virtual ~PluginMsgall() = default;

  // API
  virtual void InitApiCalls() override;
  PLUGIN_API_CALL(Api_Say);
};

#endif
