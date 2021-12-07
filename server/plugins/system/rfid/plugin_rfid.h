#ifndef _PLUGINRFID_H_
#define _PLUGINRFID_H_

#include "plugininterface.h"

class PluginRFID
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.system.rfid" )

public:
  PluginRFID();

  virtual bool HttpRequestHandle(HTTPRequest &) override;
  virtual void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &) override;

  virtual const QString GetVersion(void) override { return "1.1.0"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.1.0", "Add support for bad bunnies");
    return revisions;
  }

private:
  virtual ~PluginRFID() = default;

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_API_CALL(Api_GetLastTag);
  PLUGIN_API_CALL(Api_GetLastTagForBunny);
  PLUGIN_BUNNY_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_GetLastBunnyTag);
};

#endif
