#include <QDateTime>
#include <QUrl>
#include <QCryptographicHash>
#include <QMapIterator>
#include "tts_pico.h"
#include "log.h"

/*
root@ojn:/home/pico [04:47] $ pico2wave -l fr-FR -w test.wav "Vous venez de recevoir un imèile"
root@ojn:/home/pico [04:48] $ ffmpeg -i input.wav -vn -ar 44100 -ac 2 -ab 192k -y -f mp3 son.mp3 >/dev/null 2>&1
en-US
en-GB
de-DE
es-ES
fr-FR
it-IT
*/
TTSPico::TTSPico():TTSInterface("pico", "Pico")
{
	Voice fr;
	fr.name = "fr-FR";
	fr.language = "fr";
	fr.genre = Voice::Woman;
	fr.limit = 100;
	voiceList.insert("fr-FR", fr);

	Voice it;
	it.name = "it-IT";
	it.language = "it";
	it.genre = Voice::Woman;
	it.limit = 100;
	voiceList.insert("it-IT", it);

	Voice us;
	us.name = "en-US";
	us.language = "us";
	us.genre = Voice::Woman;
	us.limit = 100;
	voiceList.insert("en-US", us);

	Voice uk;
	uk.name = "en-UK";
	uk.language = "uk";
	uk.genre = Voice::Woman;
	uk.limit = 100;
	voiceList.insert("en-UK", uk);

	Voice es;
	es.name = "es-ES";
	es.language = "es";
	es.genre = Voice::Woman;
	es.limit = 100;
	voiceList.insert("es-ES", es);

	Voice de;
	de.name = "de-DE";
	de.language = "de";
	de.genre = Voice::Woman;
	de.limit = 100;
	voiceList.insert("de-DE", de);
}

TTSPico::~TTSPico()
{
}

QString TTSPico::CreateNewSound(QString text, QString voice, bool forceOverwrite)
{
	if(!voiceList.contains(voice))
		voice = "fr-FR";

	QString voiceClean = voice;
	voiceClean.remove("-");
	// Check (and create if needed) output folder
	QDir outputFolder = ttsFolder;
	if(!outputFolder.exists(voiceClean))
		outputFolder.mkdir(voiceClean);

	if(!outputFolder.cd(voiceClean))
	{
		LogError(QString("Cant create TTS Folder : %1").arg(ttsFolder.absoluteFilePath(voiceClean)));
		return QString();
	}

	// Compute fileName
	QString fileName = QCryptographicHash::hash(text.toLatin1(), QCryptographicHash::Md5).toHex().append(".mp3");
	QString fileWav = "/tmp/" + QCryptographicHash::hash(text.toLatin1(), QCryptographicHash::Md5).toHex().append(".wav");
	QString filePath = outputFolder.absoluteFilePath(fileName);

	//QString tempfileName = filename.append(".wav");
	//QString tempfilePath = outputFolder.absoluteFilePath(tempfileName);

	if(!forceOverwrite && QFile::exists(filePath))
		return "FROM_CACHE" + ttsHTTPUrl.arg(voiceClean, fileName);

/*
	QFile file(filePath);
	if (!file.open(QIODevice::WriteOnly))
	{
		LogError("Cannot open sound file for writing");
		return QString();
	}
*/

	QString program = "/usr/bin/pico2wave";
	QStringList arguments;
	arguments << "-l";
	arguments << voice;
	arguments << "-w";
	arguments << fileWav;
	arguments << "\"" + text.remove("\"") + "\"";
  //LogDebug(QString("%1 %2").arg(program, arguments.join(" ")));

	char buffer[1024];
	FILE* fd = popen(QString("%1 %2 >/dev/null 2>&1").arg(program, arguments.join(" ")).toLatin1(), "r");
	if (fd != NULL) {

		while(NULL != fgets(buffer, sizeof(buffer), fd)) {
			QString s(buffer);
		}
		pclose (fd);
	}
	program = "/usr/bin/ffmpeg";
	arguments.clear();
	arguments << "-i";
	arguments << fileWav;
	arguments << "-vn";
	arguments << "-ar";
	arguments << "44100";
	arguments << "-ac";
	arguments << "1";
	arguments << "-ab";
	arguments << "64k";
	arguments << "-y";
	arguments << "-f";
	arguments << "mp3";
	arguments << filePath;
	//DebugLog(QString("%1 %2").arg(program, arguments.join(" ")));

	fd = popen(QString("%1 %2 >/dev/null 2>&1").arg(program, arguments.join(" ")).toLatin1(), "r");
	if (fd != NULL) {

		while(NULL != fgets(buffer, sizeof(buffer), fd)) {
			QString s(buffer);
		}
		pclose (fd);
	}
	QFile::remove(fileWav);

	return ttsHTTPUrl.arg(voiceClean, fileName);
}

