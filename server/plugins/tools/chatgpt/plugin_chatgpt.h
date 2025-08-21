#ifndef _PLUGINCHATGPT_H_
#define _PLUGINCHATGPT_H_

#include "plugininterface.h"
#include <QNetworkAccessManager>
#include <QNetworkReply>
#include <QSet>

class PluginChatGPT
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.chatgpt")

public:
  PluginChatGPT();

  virtual const QString GetVersion(void) override { return "1.1.0"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.1.0", "Add voice interaction via head button press");
    revisions.insert("1.0.0", "Initial ChatGPT integration plugin");
    return revisions;
  }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("ask", "string");
    return list;
  }

  // Voice interaction support
  virtual bool OnClick(Bunny *b, PluginInterface::ClickType type) override;
  virtual bool OnRecord(Bunny *b, QString const& filename) override;

public slots:
  QString OnApiAsk(Bunny *, QVariant);

private slots:
  void onChatGPTResponse(QNetworkReply* reply);
  void onSpeechRecognitionResponse(QNetworkReply* reply);

private:
  virtual ~PluginChatGPT() = default;

  // Text ChatGPT methods
  bool askChatGPT(Bunny *b, const QString& question);
  bool askChatGPTFromVoice(Bunny *b, const QString& recognizedText);
  QString buildJsonRequest(const QString& message, bool isVoiceResponse = false);
  
  // Voice processing methods
  bool startVoiceRecording(Bunny *b);
  bool processVoiceRecording(Bunny *b, const QString& filename);
  QString convertToFlac(const QString& wavFile);
  QString recognizeSpeech(const QString& flacFile, Bunny *b);
  
  // User feedback methods
  void playRecordingPrompt(Bunny *b);
  void playProcessingFeedback(Bunny *b);
  void handleVoiceError(Bunny *b, const QString& error);
  
  // Utility methods
  bool checkUserPermissions(Bunny *b);
  QString generateRecordingFilename(Bunny *b);
  QString makeLanguage(const QString& lng);
  QString parseGoogleSpeechResponse(const QString& response);
  
  // API
  void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_Ask);

  QNetworkAccessManager *networkManager;
  QNetworkAccessManager *speechNetworkManager;
  
  // Voice recording tracking
  QSet<QByteArray> bunniesWaitingForVoice;
};

#endif