#include <QDateTime>
#include <QCryptographicHash>
#include <QMapIterator>
#include <QUrl>
#include <QNetworkRequest>
#include <QNetworkReply>
#include "tts_bluemix.h"
#include "log.h"

#include <iostream>
TTSbluemix::TTSbluemix():TTSInterface("bluemix", "bluemix")
{
  Voice v;
  v.limit = 5000;

  /*
  en-US_AllisonVoice
  en-US_AllisonV3Voice
  en-US_LisaVoice
  en-US_LisaV3Voice
  en-US_MichaelVoice
  en-US_MichaelV3Voice
  ar-AR_OmarVoice
  pt-BR_IsabelaVoice
  pt-BR_IsabelaV3Voice
  en-GB_KateVoice
  en-GB_KateV3Voice
  es-ES_EnriqueVoice
  es-ES_EnriqueV3Voice
  es-ES_LauraVoice
  es-ES_LauraV3Voice
  es-LA_SofiaVoice
  es-LA_SofiaV3Voice
  es-US_SofiaVoice
  es-US_SofiaV3Voice
  fr-FR_ReneeVoice
  fr-FR_ReneeV3Voice
  de-DE_BirgitVoice
  de-DE_BirgitV3Voice
  de-DE_DieterVoice
  de-DE_DieterV3Voice
  it-IT_FrancescaVoice
  it-IT_FrancescaV3Voice
  nl-NL_EmmaVoice
  nl-NL_LiamVoice
  ja-JP_EmiVoice
  ja-JP_EmiV3Voice
  zh-CN_LiNaVoice
  zh-CN_WangWeiVoice
  zh-CN_ZhangJingVoice
  */

    // FR
  v.language = "fr";

  v.name = "fr-FR_ReneeVoice";
  v.label = "Renee";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "fr-FR_ReneeV3Voice";
  v.label = "ReneeV3";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

    // EN
  v.language = "en";

  v.name = "en-US_AllisonVoice";
  v.label = "Allison";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-US_AllisonV3Voice";
  v.label = "AllisonV3";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-US_LisaVoice";
  v.label = "Lisa";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-US_LisaV3Voice";
  v.label = "LisaV3";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-US_MichaelVoice";
  v.label = "Michael";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

  v.name = "en-US_MichaelV3Voice";
  v.label = "MichaelV3";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

  v.name = "en-GB_KateVoice";
  v.label = "Kate";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-GB_KateV3Voice";
  v.label = "KateV3";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);
}

TTSbluemix::~TTSbluemix()
{
}

QString TTSbluemix::CreateNewSound(QString text, QString voice, bool forceOverwrite)
{
  QEventLoop loop;

  if(!voiceList.contains(voice))
  {
    QString& fb_voice = voiceList.first().label;
    LogDebug(QString("TTS Bluemix: Unknown voice %1, fallback to %2").arg(voice).arg(fb_voice));
    voice = fb_voice;
  }

  // Check (and create if needed) output folder
  QDir outputFolder = ttsFolder;
  if(!outputFolder.exists(voice))
    outputFolder.mkdir(voice);

  if(!outputFolder.cd(voice))
  {
    LogError(QString("TTS Bluemix: Cant create TTS Folder : %1").arg(ttsFolder.absoluteFilePath(voice)));
    return QString();
  }

  // Compute fileName
  QString fileName = QCryptographicHash::hash(text.toLatin1(), QCryptographicHash::Md5).toHex().append(".mp3");
  QString filePath = outputFolder.absoluteFilePath(fileName);

  if(!forceOverwrite && QFile::exists(filePath))
  {
    //LogWarning(QString("TTS Bluemix: Reuse file"));
    return ttsHTTPUrl.arg(voice, fileName).toLatin1();
  }

  //https://text-to-speech-demo.ng.bluemix.net/api/v1/synthesize?text=Bonsoir%2C%20il%20est%2020h35&voice=fr-FR_ReneeV3Voice&download=true&accept=audio%2Fmp3

  // Fetch MP3
  QNetworkRequest req(QUrl("https://text-to-speech-demo.ng.bluemix.net/api/v1/synthesize?voice="+voice+"&download=true&accept=audio%2Fmp3&text="+QUrl::toPercentEncoding(text)));

  QNetworkReply* rep = _http.get(req);
  QObject::connect(rep, &QNetworkReply::finished, &loop, &QEventLoop::quit);
  QObject::connect(rep, qOverload<QNetworkReply::NetworkError>(&QNetworkReply::error), &loop, &QEventLoop::quit);
  loop.exec();

  const auto& answer = rep->readAll();
  if(answer.size() == 0)
  {
    LogError("TTS Bluemix: Empty file =(");
    delete rep;
    return QString();
  }

  QFile file(filePath);
  if (!file.open(QIODevice::WriteOnly))
  {
    LogError("TTS Bluemix: Cannot open sound file for writing");
    delete rep;
    return QString();
  }
  file.write(answer);
  file.close();
  delete rep;
  return ttsHTTPUrl.arg(voice, fileName).toLatin1();
}


