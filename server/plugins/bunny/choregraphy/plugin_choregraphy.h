#ifndef _PLUGINCHOREGRAPHY_H_
#define _PLUGINCHOREGRAPHY_H_

#include "plugininterface.h"

class PluginChoregraphy
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.choregraphy" )

public:
  PluginChoregraphy();

  virtual void BeforeSendMessage(Bunny *, MessagePacket *, QString) override;

  virtual const QString GetVersion(void) override { return "1.0.0"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.0.0", "Initial revision");
    return revisions;
  }

private:
  virtual ~PluginChoregraphy() = default;

  // API
  virtual void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_Config);
};

#endif
