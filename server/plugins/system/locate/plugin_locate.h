#ifndef _PLUGINLOCATE_H_
#define _PLUGINLOCATE_H_

#include "plugininterface.h"
#include "httprequest.h"

class PluginLocate
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)

  Q_PLUGIN_METADATA(IID "ojn.plugin.system.locate")

public:
  PluginLocate();

  virtual bool HttpRequestHandle(HTTPRequest &) override;
  virtual void OnBunnyConnect(Bunny *) override;

  virtual const QString GetVersion(void) override { return "1.6.1"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override;

private:
  virtual ~PluginLocate() = default;

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_API_CALL(Api_Server);
  PLUGIN_BUNNY_API_CALL(Api_SetCustomLocateSetting);
  PLUGIN_BUNNY_API_CALL(Api_GetCustomLocateSetting);
  PLUGIN_BUNNY_API_CALL(Api_BunnyServer);
  PLUGIN_BUNNY_API_CALL(Api_BunnyConfig);
  PLUGIN_BUNNY_API_CALL(Api_BunnyCustom);

  QStringList configList;
  QStringList customList;
  QStringList waitingBunnies;
  QStringList failingBunnies;
};

#endif
