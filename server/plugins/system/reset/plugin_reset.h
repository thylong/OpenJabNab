#ifndef _PLUGINDEBUG_H_
#define _PLUGINDEBUG_H_

#include <QList>
#include <QMap>
#include <QPair>
#include <QStringList>
#include <QTime>

#include "plugininterface.h"
#include "httprequest.h"

class PluginReset
  : public PluginInterface
{
  Q_OBJECT

  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.system.reset")

public:
  PluginReset();
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;

  virtual const QString GetVersion(void) override { return "0.1.0"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("0.1.0", "Initial version");
    return revisions;
  }

private:
  virtual ~PluginReset() = default;
  void RemoveReset();

  // API
  virtual void InitApiCalls() override;
  PLUGIN_API_CALL(Api_Reset);

  QMap<QByteArray, QDateTime> resettingTime;
  QMap<QByteArray, QString> newAccount;
};

#endif
