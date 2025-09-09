#ifndef _PLUGINTTS_H_
#define _PLUGINTTS_H_

#include "plugininterface.h"

class PluginTTS
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.tts")

public:
  PluginTTS();

  virtual const QString GetVersion(void) override { return "1.2.0"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.2.0", "Add support for Nabaztag V1");
    return revisions;
  }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("say", "string");
    return list;
  }

public slots:
  QString OnApiSay(Bunny *, QVariant);

private:
  virtual ~PluginTTS() = default;

  bool sayText(Bunny *b, const QString& str);
  bool sayText(Bunny *b, const QString& str, const QString& voice, const QString& language);

  // API
  void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_Say);
};

#endif
