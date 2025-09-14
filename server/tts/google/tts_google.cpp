#include <QDateTime>
#include <QUrl>
#include <QCryptographicHash>
#include <QMapIterator>
#include <QNetworkRequest>
#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QEventLoop>
#include <QTimer>
#include <QUrlQuery>
#include <QProcessEnvironment>
#include <QDir>
#include <QStandardPaths>
#include "tts_google.h"
#include "log.h"

TTSGoogle::TTSGoogle():TTSInterface("google", "Google Text-to-Speech")
{
	networkManager = new QNetworkAccessManager(this);
	initializeGoogleVoices();
}

TTSGoogle::~TTSGoogle()
{
	if(networkManager)
		delete networkManager;
}

void TTSGoogle::initializeGoogleVoices()
{
	// Define popular Google TTS voices that are commonly available
	struct GoogleVoiceInfo {
		QString name;
		QString language;
		Voice::VoiceGenre genre;
		QString langCode;
	};

	QList<GoogleVoiceInfo> googleVoices = {
		// English voices
		{"en-US-Standard-A", "en", Voice::Woman, "en-US"},
		{"en-US-Standard-B", "en", Voice::Man, "en-US"},
		{"en-US-Standard-C", "en", Voice::Woman, "en-US"},
		{"en-US-Standard-D", "en", Voice::Man, "en-US"},
		{"en-US-Standard-E", "en", Voice::Woman, "en-US"},
		{"en-US-Standard-F", "en", Voice::Woman, "en-US"},
		{"en-US-Standard-G", "en", Voice::Woman, "en-US"},
		{"en-US-Standard-H", "en", Voice::Woman, "en-US"},
		{"en-US-Standard-I", "en", Voice::Man, "en-US"},
		{"en-US-Standard-J", "en", Voice::Man, "en-US"},
		
		// French voices
		{"fr-FR-Standard-A", "fr", Voice::Woman, "fr-FR"},
		{"fr-FR-Standard-B", "fr", Voice::Man, "fr-FR"},
		{"fr-FR-Standard-C", "fr", Voice::Woman, "fr-FR"},
		{"fr-FR-Standard-D", "fr", Voice::Man, "fr-FR"},
		
		// German voices
		{"de-DE-Standard-A", "de", Voice::Woman, "de-DE"},
		{"de-DE-Standard-B", "de", Voice::Man, "de-DE"},
		{"de-DE-Standard-C", "de", Voice::Woman, "de-DE"},
		{"de-DE-Standard-D", "de", Voice::Man, "de-DE"},
		
		// Spanish voices
		{"es-ES-Standard-A", "es", Voice::Woman, "es-ES"},
		{"es-ES-Standard-B", "es", Voice::Man, "es-ES"},
		
		// Italian voices
		{"it-IT-Standard-A", "it", Voice::Woman, "it-IT"},
		{"it-IT-Standard-B", "it", Voice::Man, "it-IT"},
		{"it-IT-Standard-C", "it", Voice::Woman, "it-IT"},
		{"it-IT-Standard-D", "it", Voice::Woman, "it-IT"}
	};

	for(const auto& voiceInfo : googleVoices) {
		Voice voice;
		voice.name = voiceInfo.name;
		voice.language = voiceInfo.language;
		voice.genre = voiceInfo.genre;
		voice.limit = 5000; // Google TTS supports up to 5000 characters
		voiceList.insert(voiceInfo.name, voice);
	}

	LogInfo(QString("Google TTS initialized with %1 voices").arg(voiceList.size()));
}

QString TTSGoogle::getApiKey()
{
	// Try multiple ways to get the API key
	QProcessEnvironment env = QProcessEnvironment::systemEnvironment();
	
	QString apiKey = env.value("GOOGLE_SPEECH_API_KEY");
	if (!apiKey.isEmpty()) {
		return apiKey;
	}
	
	apiKey = env.value("GOOGLE_TTS_API_KEY");
	if (!apiKey.isEmpty()) {
		return apiKey;
	}

	// Check settings or config file
	// You could also read from a config file here
	
	LogWarning("Google TTS API key not found. Set GOOGLE_SPEECH_API_KEY environment variable.");
	return QString();
}

bool TTSGoogle::downloadGoogleTTS(const QString &text, const QString &voice, const QString &outputFile)
{
	QString apiKey = getApiKey();
	if (apiKey.isEmpty()) {
		LogError("Google TTS API key not configured");
		return false;
	}

	// Google Text-to-Speech API endpoint
	QString url = QString("https://texttospeech.googleapis.com/v1/text:synthesize?key=%1").arg(apiKey);
	
	// Prepare the request body
	QJsonObject request;
	
	// Input text
	QJsonObject input;
	input["text"] = text;
	request["input"] = input;
	
	// Voice configuration
	QJsonObject voiceConfig;
	QString languageCode = voice.split("-").mid(0, 2).join("-"); // Extract language code (e.g., "en-US" from "en-US-Standard-A")
	voiceConfig["languageCode"] = languageCode;
	voiceConfig["name"] = voice;
	request["voice"] = voiceConfig;
	
	// Audio configuration
	QJsonObject audioConfig;
	audioConfig["audioEncoding"] = "MP3";
	audioConfig["sampleRateHertz"] = 22050;
	request["audioConfig"] = audioConfig;
	
	QJsonDocument doc(request);
	QByteArray data = doc.toJson(QJsonDocument::Compact);
	
	// Make the HTTP request
	QNetworkRequest netRequest;
	netRequest.setUrl(QUrl(url));
	netRequest.setHeader(QNetworkRequest::ContentTypeHeader, "application/json");
	
	QNetworkReply *reply = networkManager->post(netRequest, data);
	
	// Wait for the response
	QEventLoop loop;
	QTimer timer;
	timer.setSingleShot(true);
	timer.setInterval(30000); // 30 second timeout
	
	connect(reply, &QNetworkReply::finished, &loop, &QEventLoop::quit);
	connect(&timer, &QTimer::timeout, &loop, &QEventLoop::quit);
	
	timer.start();
	loop.exec();
	
	if (!timer.isActive()) {
		LogError("Google TTS request timeout");
		reply->deleteLater();
		return false;
	}
	
	timer.stop();
	
	if (reply->error() != QNetworkReply::NoError) {
		LogError(QString("Google TTS API error: %1").arg(reply->errorString()));
		reply->deleteLater();
		return false;
	}
	
	// Parse the response
	QByteArray responseData = reply->readAll();
	reply->deleteLater();
	
	QJsonDocument responseDoc = QJsonDocument::fromJson(responseData);
	if (!responseDoc.isObject()) {
		LogError("Invalid Google TTS response format");
		return false;
	}
	
	QJsonObject responseObj = responseDoc.object();
	if (!responseObj.contains("audioContent")) {
		LogError("Google TTS response missing audioContent");
		return false;
	}
	
	// Decode base64 audio content and save to file
	QString audioContentB64 = responseObj["audioContent"].toString();
	QByteArray audioData = QByteArray::fromBase64(audioContentB64.toUtf8());
	
	QFile file(outputFile);
	if (!file.open(QIODevice::WriteOnly)) {
		LogError(QString("Cannot write Google TTS output file: %1").arg(outputFile));
		return false;
	}
	
	file.write(audioData);
	file.close();
	
	LogInfo(QString("Google TTS audio saved: %1 (%2 bytes)").arg(outputFile).arg(audioData.size()));
	return true;
}

QString TTSGoogle::CreateNewSound(QString text, QString voice, bool forceOverwrite)
{
	if(!voiceList.contains(voice)) {
		LogWarning(QString("Google TTS voice not found: %1, using default").arg(voice));
		voice = "en-US-Standard-A"; // Default to English female voice
	}

	QString voiceClean = voice;
	voiceClean.replace("-", "");
	
	// Check (and create if needed) output folder
	QDir outputFolder = ttsFolder;
	if(!outputFolder.exists(voiceClean))
		outputFolder.mkdir(voiceClean);

	if(!outputFolder.cd(voiceClean))
	{
		LogError(QString("Cannot create Google TTS folder: %1").arg(ttsFolder.absoluteFilePath(voiceClean)));
		return QString();
	}

	// Compute fileName
	QString fileName = QCryptographicHash::hash(text.toUtf8(), QCryptographicHash::Md5).toHex().append(".mp3");
	QString filePath = outputFolder.absoluteFilePath(fileName);

	// Return cached file if it exists and we're not forcing overwrite
	if(!forceOverwrite && QFile::exists(filePath))
		return "FROM_CACHE" + ttsHTTPUrl.arg(voiceClean, fileName);

	// Download from Google TTS API
	if (!downloadGoogleTTS(text, voice, filePath)) {
		LogError(QString("Failed to generate Google TTS for: %1").arg(text));
		return QString();
	}

	return ttsHTTPUrl.arg(voiceClean, fileName);
}