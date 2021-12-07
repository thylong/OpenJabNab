#ifndef _PLUGINDICE_H_
#define _PLUGINDICE_H_

#include "plugininterface.h"

class PluginDice
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.dice" )

public:
  PluginDice();

  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual const QString GetVersion(void) override { return "1.2.0"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.2.0", "Add support for Nabaztag V1");
    return revisions;
  }

private:
  virtual ~PluginDice() = default;

  QStringList AdpFileToLoad(Bunny *);

  QMap<Bunny *, QStringList> soundToSend;
};

#endif
