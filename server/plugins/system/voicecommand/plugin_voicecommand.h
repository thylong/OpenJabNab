#ifndef _PLUGINVOICECOMMAND_H_
#define _PLUGINVOICECOMMAND_H_

#include <QNetworkAccessManager>

#include "plugininterface.h"

class QNetworkReply;

class PluginVoiceCommand
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.system.voicecommand" )

public:
  PluginVoiceCommand();

  virtual bool OnRecord(Bunny *, QString const&) override;

  virtual const QString GetVersion(void) override { return "1.3.0"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.3.0", "Add private keys");
    return revisions;
  }

private:
  virtual ~PluginVoiceCommand() = default;

  void recognitionFinished(QNetworkReply* rep);

  // API
  virtual void InitApiCalls() override;
  PLUGIN_API_CALL(Api_AddAuthorizedBunny);
  PLUGIN_API_CALL(Api_RemoveAuthorizedBunny);
  PLUGIN_API_CALL(Api_ListAuthorizedBunnies);
  PLUGIN_API_CALL(Api_Key);
  PLUGIN_API_CALL(Api_Bunny);
  PLUGIN_API_CALL(Api_Language);
  PLUGIN_API_CALL(Api_Words);
  PLUGIN_API_CALL(Api_Sentences);
  PLUGIN_BUNNY_API_CALL(Api_BunnyKey);

  QNetworkAccessManager _http;
  QString cleanString(QString);
  QString makeLanguage(QString);
  bool saveWords(Bunny *, QString);
  void analyzeWords(Bunny *, QString, bool);

  //bool OnVoiceBeforeBunny(QString const&, QString const&);
  //bool OnVoiceAfterBunny(QString const&, QString const&);
};
#endif
