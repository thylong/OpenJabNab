#ifndef _PLUGINDEBUG_H_
#define _PLUGINDEBUG_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>

#include "plugininterface.h"

class PluginDebug
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)

  Q_PLUGIN_METADATA(IID "ojn.plugin.system.debug" )

public:
  PluginDebug();

  virtual bool XmppBunnyMessage(Bunny *, QByteArray const&) override;
  virtual const QString GetVersion(void) override { return "0.1.0"; }

private:
  virtual ~PluginDebug() = default;

  // API
  void InitApiCalls();
  PLUGIN_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_Info);
};

#endif
