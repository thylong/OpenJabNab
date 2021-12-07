#ifndef _PLUGINEARINFO_H_
#define _PLUGINEARINFO_H_

#include "plugininterface.h"

class PluginEarinfo
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.earinfo" )

public:
  PluginEarinfo();

  virtual const QString GetVersion(void) override { return "1.0.1"; }

  virtual bool OnEarsMove(Bunny *, int, int) override;

private:
  virtual ~PluginEarinfo() = default;
};

#endif
