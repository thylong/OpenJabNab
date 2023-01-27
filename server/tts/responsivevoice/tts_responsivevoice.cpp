#include <QDateTime>
#include <QCryptographicHash>
#include <QMapIterator>
#include <QNetworkAccessManager>
#include <QUrl>
#include <QNetworkRequest>
#include <QNetworkReply>
#include "tts_responsivevoice.h"
#include "log.h"

#include <iostream>
TTSresponsivevoice::TTSresponsivevoice():TTSInterface("responsivevoice", "responsivevoice")
{
  Voice v;
  v.limit = 5000;
    // FR
  v.language = "fr";

  v.name = "fr_female";
  v.label = "French Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "fr_male";
  v.label = "French Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

  v.language = "en";
    // en-US
  v.name = "en-US_female";
  v.label = "US English Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-US_male";
  v.label = "US English Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);
    // en-GB
  v.name = "en-GB_female";
  v.label = "UK English Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-GB_male";
  v.label = "UK English Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);
    // en-AU
  v.name = "en-AU_female";
  v.label = "Australian Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "en-AU_male";
  v.label = "Australian Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

    // de-DE
  v.language = "de";
  v.name = "de_female";
  v.label = "Deutsch Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "de_male";
  v.label = "Deutsch Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

    // it-IT
  v.language = "it";
  v.name = "it_female";
  v.label = "Italian Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "it_male";
  v.label = "Italian Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);
    // ru-RU
  v.language = "ru";
  v.name = "ru_female";
  v.label = "Russian Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "ru_male";
  v.label = "Russian Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);
    // es-ES
  v.language = "es";
  v.name = "es_female";
  v.label = "Spanish Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "es_male";
  v.label = "Spanish Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

  v.name = "es-419_female";
  v.label = "Spanish Latin American Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "es-419_male";
  v.label = "Spanish Latin American Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

    // ar-AR
  v.language = "ar";
  v.name = "ar_female";
  v.label = "Arabic Female";
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = "ar_male";
  v.label = "Arabic Male";
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);

  /*
    v.language = "de";
  v.name = " Female";
  v.label = v.name;
  v.genre = Voice::Woman;
  voiceList.insert(v.name, v);

  v.name = " Male";
  v.label = v.name;
  v.genre = Voice::Man;
  voiceList.insert(v.name, v);
*/

}

TTSresponsivevoice::~TTSresponsivevoice()
{
}

QString TTSresponsivevoice::CreateNewSound(QString text, QString voice, bool forceOverwrite)
{
  QEventLoop loop;

  if(!voiceList.contains(voice))
  {
    LogDebug(QString("TTS ReponsiveVoice: Unknown voice %1, fallback to fr_female").arg(voice));
    voice = "fr_female";
  }

  auto vl = voice.split("_");
  QString voice2 = vl.at(0);
  QString gender = vl.at(1);

  // Check (and create if needed) output folder
  QDir outputFolder = ttsFolder;
  if(!outputFolder.exists(voice))
    outputFolder.mkdir(voice);

  if(!outputFolder.cd(voice))
  {
    LogError(QString("TTS ReponsiveVoice: Cant create TTS Folder : %1").arg(ttsFolder.absoluteFilePath(voice)));
    return QString();
  }

  // Compute fileName
  QString fileName = QCryptographicHash::hash(text.toLatin1(), QCryptographicHash::Md5).toHex().append(".mp3");
  QString filePath = outputFolder.absoluteFilePath(fileName);

  if(!forceOverwrite && QFile::exists(filePath))
    {
        //LogWarning(QString("TTS ReponsiveVoice: Reuse file"));
    return ttsHTTPUrl.arg(voice, fileName).toLatin1();
    }

    // Fetch MP3
    // Old
    // QNetworkRequest req(QUrl("http://code.responsivevoice.org/getvoice.php?tl="+voice2+"&gender="+gender+"&key=WGciAW2s&t="+QUrl::toPercentEncoding(text)));
    // New
    https://texttospeech.responsivevoice.org/v1/text:synthesize?lang=en-GB&key=WfWmvaX0&gender=female&text=Hello%20it%20is%205%20pm
    QNetworkRequest req(QUrl("https://texttospeech.responsivevoice.org/v1/text:synthesize?lang="+voice2+"&gender="+gender+"&key=WfWmvaX0&text="+QUrl::toPercentEncoding(text)));

    auto* rep = _http.get(req);
    QObject::connect(rep, &QNetworkReply::finished, &loop, &QEventLoop::quit);
    QObject::connect(rep, qOverload<QNetworkReply::NetworkError>(&QNetworkReply::errorOccurred), &loop, &QEventLoop::quit);
    loop.exec();

    const auto answer = rep->readAll();
    delete rep;
    if(answer.size() == 0)
    {
      LogError("TTS ReponsiveVoice: Empty file =(");
      return QString();
    }

  QFile file(filePath);
  if (!file.open(QIODevice::WriteOnly))
  {
    LogError("TTS ReponsiveVoice: Cannot open sound file for writing");
    return QString();
  }
  file.write(answer);
  file.close();
  return ttsHTTPUrl.arg(voice, fileName).toLatin1();
}
