#ifndef _PLUGINCHATGPT_H_
#define _PLUGINCHATGPT_H_

#include "plugininterface.h"
#include <QNetworkAccessManager>
#include <QNetworkReply>

class PluginChatGPT
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.chatgpt")

public:
  PluginChatGPT();

  virtual const QString GetVersion(void) override { return "1.0.0"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.0.0", "Initial ChatGPT integration plugin");
    return revisions;
  }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("ask", "string");
    return list;
  }

public slots:
  QString OnApiAsk(Bunny *, QVariant);

private slots:
  void onChatGPTResponse(QNetworkReply* reply);

private:
  virtual ~PluginChatGPT() = default;

  bool askChatGPT(Bunny *b, const QString& question);
  QString buildJsonRequest(const QString& message);
  
  // API
  void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_Ask);

  QNetworkAccessManager *networkManager;
};

#endif