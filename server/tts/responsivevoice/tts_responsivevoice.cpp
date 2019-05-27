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
	v.name="fr";
	v.label="fr";
	v.genre = Voice::Unknow;
	v.language_id="fr0";
	v.language = "fr";
	v.limit = 0;
	voiceList.insert("fr",v);
}

TTSresponsivevoice::~TTSresponsivevoice()
{
}

QString TTSresponsivevoice::CreateNewSound(QString text, QString voice, bool forceOverwrite)
{
	QEventLoop loop;

	if(!voiceList.contains(voice))
		voice = "fr";

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
    QNetworkAccessManager http;
    QNetworkRequest req(QUrl("http://code.responsivevoice.org/getvoice.php?tl=fr&t="+QUrl::toPercentEncoding(text)));

    QNetworkReply* rep = http.get(req);
    QObject::connect(rep, SIGNAL(finished()), &loop, SLOT(quit()));
    QObject::connect(rep, SIGNAL(error(QNetworkReply::NetworkError)), &loop, SLOT(quit()));
    loop.exec();

    const auto& answer = rep->readAll();
    if(answer.size() == 0)
    {
        LogError("TTS ReponsiveVoice: Empty file =(");
        delete rep;
		return QString();
    }

	QFile file(filePath);
	if (!file.open(QIODevice::WriteOnly))
	{
		LogError("TTS ReponsiveVoice: Cannot open sound file for writing");
        delete rep;
		return QString();
	}
	file.write(answer);
	file.close();
    delete rep;
	return ttsHTTPUrl.arg(voice, fileName).toLatin1();
}


