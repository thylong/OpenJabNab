#include <QDateTime>
#include <QNetworkAccessManager>
#include <QUrl>
#include <QRegExp>
#include <QNetworkRequest>
#include <QNetworkReply>
#include <QCryptographicHash>
#include <QMapIterator>
#include "tts_notevibes.h"
#include "log.h"

TTSNotevibes::TTSNotevibes():TTSInterface("notevibes", "Notevibes")
{
	Voice fr;
	fr.limit = 5000;
		// fr-FR
	fr.language = "fr";

	fr.name = "fr-FR-Wavenet-A";
	fr.label = "Marie";
	fr.genre = Voice::Woman;
	voiceList.insert("fr-FR-Wavenet-A", fr);

	fr.name = "fr-FR-Wavenet-B";
	fr.label = "Thomas";
	fr.genre = Voice::Man;
	voiceList.insert("fr-FR-Wavenet-B", fr);

	fr.name = "fr-FR-Wavenet-C";
	fr.label = "Chloe";
	fr.genre = Voice::Woman;
	voiceList.insert("fr-FR-Wavenet-C", fr);

	fr.name = "fr-FR-Wavenet-D";
	fr.label = "Julien";
	fr.genre = Voice::Man;
	voiceList.insert("fr-FR-Wavenet-D", fr);

		// en-US
	fr.language = "en";

	fr.name = "en-US-Standard-B";
	fr.label = "Rick";
	fr.genre = Voice::Man;
	voiceList.insert("en-US-Standard-B", fr);

	fr.name = "en-US-Standard-C";
	fr.label = "Lucy";
	fr.genre = Voice::Woman;
	voiceList.insert("en-US-Standard-C", fr);

	fr.name = "en-US-Standard-D";
	fr.label = "David";
	fr.genre = Voice::Man;
	voiceList.insert("en-US-Standard-D", fr);

	fr.name = "en-US-Standard-E";
	fr.label = "Kate";
	fr.genre = Voice::Woman;
	voiceList.insert("en-US-Standard-E", fr);
		// en-GB
	fr.name = "en-GB-Wavenet-A";
	fr.label = "Lola";
	fr.genre = Voice::Woman;
	voiceList.insert("en-GB-Wavenet-A", fr);

	fr.name = "en-GB-Wavenet-B";
	fr.label = "Donald";
	fr.genre = Voice::Man;
	voiceList.insert("en-GB-Wavenet-B", fr);

	fr.name = "en-GB-Wavenet-C";
	fr.label = "Gabriela";
	fr.genre = Voice::Woman;
	voiceList.insert("en-GB-Wavenet-C", fr);

	fr.name = "en-GB-Wavenet-D";
	fr.label = "Daniel";
	fr.genre = Voice::Man;
	voiceList.insert("en-GB-Wavenet-D", fr);

		// en-AU
	fr.name = "en-AU-Wavenet-A";
	fr.label = "Kia";
	fr.genre = Voice::Woman;
	voiceList.insert("en-AU-Wavenet-A", fr);

	fr.name = "en-AU-Wavenet-B";
	fr.label = "William";
	fr.genre = Voice::Man;
	voiceList.insert("en-AU-Wavenet-B", fr);

	fr.name = "en-AU-Wavenet-C";
	fr.label = "Olivia";
	fr.genre = Voice::Woman;
	voiceList.insert("en-AU-Wavenet-C", fr);

	fr.name = "en-AU-Wavenet-D";
	fr.label = "Noah";
	fr.genre = Voice::Woman;
	voiceList.insert("en-AU-Wavenet-D", fr);

		// de-DE
	fr.language = "de";

	fr.name = "de-DE-Wavenet-A";
	fr.label = "Anika";
	fr.genre = Voice::Woman;
	voiceList.insert("de-DE-Wavenet-A", fr);

	fr.name = "de-DE-Wavenet-B";
	fr.label = "Markus";
	fr.genre = Voice::Man;
	voiceList.insert("de-DE-Wavenet-B", fr);

	fr.name = "de-DE-Wavenet-C";
	fr.label = "Arabella";
	fr.genre = Voice::Woman;
	voiceList.insert("de-DE-Wavenet-C", fr);

	fr.name = "de-DE-Wavenet-D";
	fr.label = "Gust";
	fr.genre = Voice::Man;
	voiceList.insert("de-DE-Wavenet-D", fr);

		// it-IT
	fr.name = "it-IT-Wavenet-A";
	fr.label = "Sofia";
	fr.genre = Voice::Woman;
	voiceList.insert("it-IT-Wavenet-A", fr);

		// ru-RU
	fr.name = "ru-RU-Wavenet-A";
	fr.label = "Yana";
	fr.genre = Voice::Woman;
	voiceList.insert("ru-RU-Wavenet-A", fr);

	fr.name = "ru-RU-Wavenet-B";
	fr.label = "Denis";
	fr.genre = Voice::Man;
	voiceList.insert("ru-RU-Wavenet-B", fr);

	fr.name = "ru-RU-Wavenet-C";
	fr.label = "Alice";
	fr.genre = Voice::Woman;
	voiceList.insert("ru-RU-Wavenet-C", fr);

	fr.name = "ru-RU-Wavenet-D";
	fr.label = "Dima";
	fr.genre = Voice::Man;
	voiceList.insert("ru-RU-Wavenet-D", fr);
}

TTSNotevibes::~TTSNotevibes()
{
}

QString TTSNotevibes::CreateNewSound(QString text, QString voice, bool forceOverwrite)
{
	QEventLoop loop;

	// Check (and create if needed) output folder
	QDir outputFolder = ttsFolder;
	if(!outputFolder.exists(voice))
		outputFolder.mkdir(voice);

	if(!outputFolder.cd(voice))
	{
		LogError(QString("Cant create TTS Folder : %1").arg(ttsFolder.absoluteFilePath(voice)));
		return QString();
	}

	// Compute fileName
	QString fileName = QCryptographicHash::hash(text.toLatin1(), QCryptographicHash::Md5).toHex().append(".mp3");
	QString filePath = outputFolder.absoluteFilePath(fileName);

	if(!forceOverwrite && QFile::exists(filePath))
		return "FROM_CACHE" + ttsHTTPUrl.arg(voice, fileName);

	// Fetch MP3
  QNetworkAccessManager http;
  QNetworkRequest req(QUrl("http://notevibes.com/"));
  req.setRawHeader("Content-type","application/x-www-form-urlencoded");
  QByteArray ContentData;
  ContentData += "content=" + QUrl::toPercentEncoding(text) + "&voice=" + voice;

  QNetworkReply* rep = http.post(req,ContentData);
  QObject::connect(rep, SIGNAL(finished()), &loop, SLOT(quit()));
  QObject::connect(rep, SIGNAL(error(QNetworkReply::NetworkError)), &loop, SLOT(quit()));
  loop.exec();

  const auto& answer = rep->readAll();
  QString traceCtx = rep->rawHeader("X-Cloud-Trace-Context");
  //LogDebug(QString("Got: %1, %2").arg(rep->error()).arg(traceCtx));//.arg(QStrinanswer));
  QRegExp rx("<a href='([^']+)'> Download MP3");
  rx.setMinimal(true);
  if(rx.indexIn(answer) != -1 )
  {
    //LogDebug(QString("URL %1").arg(rx.cap(1)));
    delete rep;
    // Get MP3
    QNetworkRequest req2(QUrl(rx.cap(1)));
    req2.setRawHeader("X-Cloud-Trace-Context", traceCtx.toLatin1());
    rep = http.get(req2);
    QObject::connect(rep, SIGNAL(finished()), &loop, SLOT(quit()));
    QObject::connect(rep, SIGNAL(error(QNetworkReply::NetworkError)), &loop, SLOT(quit()));
    loop.exec();
    const auto& mp3 = rep->readAll();
    if( rep->error() != QNetworkReply::NoError ||
        rep->attribute(QNetworkRequest::HttpStatusCodeAttribute).toInt() != 200 ||
        mp3.size() == 0
      )
    {
        LogError("TTS Notevibes: Network error or Empty file =(");
        delete rep;
        return QString();
    }
    // Save to File !
    QFile file(filePath);
    if (!file.open(QIODevice::WriteOnly))
    {
      LogError("Cannot open sound file for writing");
      return QString();
    }
        file.write(mp3);
    file.close();
    return ttsHTTPUrl.arg(voice, fileName);
  }
LogError("Notevibes demo did not return a sound file");
LogDebug(QString("Notevibes answer %1: %1").arg(rep->error()).arg(QString(answer)));
  delete rep;
return QString();
}

