#include <memory>

#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QNetworkRequest>
#include <QProcess>
#include <QTimer>
#include <QHttpMultiPart>
#include <QHttpPart>
#include <QFile>
#include <QFileInfo>
#include <QDateTime>
#include <QRegExp>

#include "plugin_chatgpt.h"

#include "bunny.h"
#include "bunnymanager.h"
#include "account.h"
#include "accountmanager.h"
#include "log.h"
#include "packets/messagepacket.h"
#include "settings.h"
#include "translator.h"
#include "tts/ttsmanager.h"

PluginChatGPT::PluginChatGPT()
	: PluginInterface("chatgpt", "ChatGPT Voice Assistant, Ask questions via button press or API",
					  BunnyV2Plugin | BunnyV1Plugin | ApiPlugin | SingleClickPlugin
					 )
{
	networkManager = new QNetworkAccessManager(this);
	speechNetworkManager = new QNetworkAccessManager(this);
	connect(networkManager, &QNetworkAccessManager::finished, this, &PluginChatGPT::onChatGPTResponse);
	connect(speechNetworkManager, &QNetworkAccessManager::finished, this, &PluginChatGPT::onSpeechRecognitionResponse);
}

/*******
 * API *
 *******/

void PluginChatGPT::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("ask(question)", &PluginChatGPT::Api_Ask);
}

bool PluginChatGPT::askChatGPT(Bunny *b, const QString& question)
{
	if(!b->IsConnected())
		return false;

	// Get API key from environment variable
	QString apiKey = QString::fromLocal8Bit(qgetenv("OPENAI_API_KEY"));
	if(apiKey.isEmpty())
	{
		// Fallback to plugin settings if environment variable not set
		apiKey = b->GetPluginSetting(GetName(), "apiKey", QString()).toString();
		if(apiKey.isEmpty())
		{
			return false;
		}
	}

	// Prepare the HTTP request
	QUrl url("https://api.openai.com/v1/chat/completions");
	QNetworkRequest request(url);
	request.setHeader(QNetworkRequest::ContentTypeHeader, "application/json");
	request.setRawHeader("Authorization", QString("Bearer %1").arg(apiKey).toUtf8());

	// Build JSON payload
	QString jsonData = buildJsonRequest(question, false);

	// Store bunny ID for response handling
	QNetworkReply* reply = networkManager->post(request, jsonData.toUtf8());
	reply->setProperty("bunnyId", b->GetID());
	reply->setProperty("question", question);

	return true;
}


void PluginChatGPT::onChatGPTResponse(QNetworkReply* reply)
{
	QByteArray bunnyId = reply->property("bunnyId").toByteArray();
	QString question = reply->property("question").toString();
	bool isVoiceRequest = reply->property("isVoiceRequest").toBool();
	
	Bunny* bunny = BunnyManager::GetBunny(bunnyId);
	if(!bunny)
	{
		reply->deleteLater();
		return;
	}

	if(reply->error() == QNetworkReply::NoError)
	{
		QByteArray responseData = reply->readAll();
		QJsonDocument jsonDoc = QJsonDocument::fromJson(responseData);
		QJsonObject jsonObj = jsonDoc.object();

		if(jsonObj.contains("choices") && jsonObj["choices"].isArray())
		{
			QJsonArray choices = jsonObj["choices"].toArray();
			if(!choices.isEmpty())
			{
				QJsonObject firstChoice = choices[0].toObject();
				QJsonObject message = firstChoice["message"].toObject();
				QString content = message["content"].toString().trimmed();

				if(!content.isEmpty())
				{
					// Use TTS to speak the response
					TTSManager::OutputFormat format = bunny->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
					TTSAnswer sound = TTSManager::CreateSound(content, bunny->GetVoice(), bunny->GetLanguage(), format, false);
					TTSLog(bunny->GetID(), GetName(), sound);

					if(bunny->GetVersion() == 1)
					{
						AddSoundToSend(bunny, sound.file);
					}
					else
					{
						bunny->SendPacket(MessagePacket("MU " + sound.file.toLatin1() + "\nMW\n"), GetName());
					}
				}
			}
		}
	}
	else
	{
		// Handle error - send error message via TTS if it was a voice request
		if(isVoiceRequest && bunny)
		{
			handleVoiceError(bunny, "Failed to get response from ChatGPT");
		}
	}

	reply->deleteLater();
}

PLUGIN_BUNNY_API_CALL(PluginChatGPT::Api_Ask)
{
	if(!askChatGPT(bunny, hRequest.GetArg("question")))
		return new ApiAnswers::Error(Translator::tr("Failed to send question to ChatGPT or bunny '%1' is not connected", account).arg(QString(bunny->GetID())));
	return new ApiAnswers::Ok(Translator::tr("Sending question '%1' to ChatGPT for bunny '%2'", account).arg(hRequest.GetArg("question"), QString(bunny->GetID())));
}

QString PluginChatGPT::OnApiAsk(Bunny *b, QVariant arg)
{
	askChatGPT(b, arg.toString());
	return QString();
}

/*******
 * VOICE INTERACTION *
 *******/

bool PluginChatGPT::OnClick(Bunny *b, PluginInterface::ClickType type)
{
	if(type == PluginInterface::SingleClick) {
		return startVoiceRecording(b);
	}
	return false;
}

bool PluginChatGPT::OnRecord(Bunny *b, QString const& filename)
{
	// Check if this bunny is waiting for voice input
	if(!bunniesWaitingForVoice.contains(b->GetID()))
	{
		return false; // Not our recording, let other plugins handle it
	}

	// Remove from waiting list
	bunniesWaitingForVoice.remove(b->GetID());

	// Play processing feedback
	playProcessingFeedback(b);

	// Process the voice recording
	return processVoiceRecording(b, filename);
}

bool PluginChatGPT::startVoiceRecording(Bunny *b)
{
	LogDebug("ChatGPT: Start voice recording for bunny " + QString(b->GetID()));
	
	if(!b->IsConnected())
	{
		LogDebug("ChatGPT: Bunny not connected");
		handleVoiceError(b, "Bunny not connected");
		return false;
	}

	// Check user permissions (registered user required)
	if(!checkUserPermissions(b))
	{
		LogDebug("ChatGPT: Permission check failed");
		handleVoiceError(b, "Voice commands require a registered user account");
		return false;
	}

	LogDebug("ChatGPT: All checks passed, starting voice recording");
	
	// Add bunny to waiting list
	bunniesWaitingForVoice.insert(b->GetID());

	// Play recording prompt
	playRecordingPrompt(b);

	// The recording will be triggered automatically by the bunny
	// and we'll receive it via OnRecord() callback
	return true;
}

bool PluginChatGPT::checkUserPermissions(Bunny *b)
{
	// More permissive than voicecommand - allow any registered user for ChatGPT
	QByteArray ownerAccount = b->GetGlobalSetting("OwnerAccount").toByteArray();
	LogDebug("ChatGPT: Checking permissions for bunny " + QString(b->GetID()) + " with owner: " + QString(ownerAccount));
	
	// If no owner account is set, deny access
	if(ownerAccount.isEmpty())
	{
		LogDebug("ChatGPT: No owner account set, access denied");
		return false;
	}
	
	Account * a = AccountManager::GetAccountByLogin(ownerAccount);
	if(a != NULL)
	{
		LogDebug("ChatGPT: Found account, checking abuse status");
		// Just check that user is not banned
		if(!a->GetAbuse())
		{
			LogDebug("ChatGPT: User permissions OK");
			return true;
		}
		else
		{
			LogDebug("ChatGPT: User is banned/abused");
		}
	}
	else
	{
		LogDebug("ChatGPT: No account found for owner: " + QString(ownerAccount));
	}
	return false;
}

void PluginChatGPT::playRecordingPrompt(Bunny *b)
{
	QString prompt = "Please speak your question after the beep";
	TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
	TTSAnswer sound = TTSManager::CreateSound(prompt, b->GetVoice(), b->GetLanguage(), format, false);
	TTSLog(b->GetID(), GetName(), sound);

	if(b->GetVersion() == 1)
	{
		AddSoundToSend(b, sound.file);
	}
	else
	{
		b->SendPacket(MessagePacket("MU " + sound.file.toLatin1() + "\nMW\n"), GetName());
	}
}

void PluginChatGPT::playProcessingFeedback(Bunny *b)
{
	QString feedback = "Processing your question, please wait";
	TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
	TTSAnswer sound = TTSManager::CreateSound(feedback, b->GetVoice(), b->GetLanguage(), format, false);
	TTSLog(b->GetID(), GetName(), sound);

	if(b->GetVersion() == 1)
	{
		AddSoundToSend(b, sound.file);
	}
	else
	{
		b->SendPacket(MessagePacket("MU " + sound.file.toLatin1() + "\nMW\n"), GetName());
	}
}

void PluginChatGPT::handleVoiceError(Bunny *b, const QString& error)
{
	QString errorMsg = "Sorry, I cannot process your voice request";
	TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;
	TTSAnswer sound = TTSManager::CreateSound(errorMsg, b->GetVoice(), b->GetLanguage(), format, false);
	TTSLog(b->GetID(), GetName(), sound);

	if(b->GetVersion() == 1)
	{
		AddSoundToSend(b, sound.file);
	}
	else
	{
		b->SendPacket(MessagePacket("MU " + sound.file.toLatin1() + "\nMW\n"), GetName());
	}
}

QString PluginChatGPT::generateRecordingFilename(Bunny *b)
{
	return QString("chatgpt_%1_%2.wav")
		.arg(QString(b->GetID()))
		.arg(QDateTime::currentDateTime().toString("yyyyMMdd_hhmmss"));
}

bool PluginChatGPT::askChatGPTFromVoice(Bunny *b, const QString& recognizedText)
{
	if(!b->IsConnected())
		return false;

	// Get API key from environment variable
	QString apiKey = QString::fromLocal8Bit(qgetenv("OPENAI_API_KEY"));
	if(apiKey.isEmpty())
	{
		// Fallback to plugin settings if environment variable not set
		apiKey = b->GetPluginSetting(GetName(), "apiKey", QString()).toString();
		if(apiKey.isEmpty())
		{
			return false;
		}
	}

	// Prepare the HTTP request
	QUrl url("https://api.openai.com/v1/chat/completions");
	QNetworkRequest request(url);
	request.setHeader(QNetworkRequest::ContentTypeHeader, "application/json");
	request.setRawHeader("Authorization", QString("Bearer %1").arg(apiKey).toUtf8());

	// Build JSON payload for voice response
	QString jsonData = buildJsonRequest(recognizedText, true);

	// Store bunny ID and context for response handling
	QNetworkReply* reply = networkManager->post(request, jsonData.toUtf8());
	reply->setProperty("bunnyId", b->GetID());
	reply->setProperty("question", recognizedText);
	reply->setProperty("isVoiceRequest", true);

	return true;
}

QString PluginChatGPT::buildJsonRequest(const QString& message, bool isVoiceResponse)
{
	QJsonObject json;
	json["model"] = "gpt-3.5-turbo";
	json["temperature"] = 0.7;
	
	// Use smaller token limit for voice responses
	json["max_tokens"] = isVoiceResponse ? 75 : 150;

	QJsonArray messages;
	QJsonObject systemMessage;
	systemMessage["role"] = "system";
	
	if(isVoiceResponse) {
		systemMessage["content"] = "You are a helpful voice assistant for a Nabaztag bunny. "
									"Keep responses very concise and conversational since they will be spoken aloud. "
									"Limit responses to 2-3 sentences maximum. Use simple language suitable for speech.";
	} else {
		systemMessage["content"] = "You are a helpful assistant. Keep responses concise and suitable for speech output.";
	}
	
	messages.append(systemMessage);

	QJsonObject userMessage;
	userMessage["role"] = "user";
	userMessage["content"] = message;
	messages.append(userMessage);

	json["messages"] = messages;

	return QJsonDocument(json).toJson(QJsonDocument::Compact);
}

bool PluginChatGPT::processVoiceRecording(Bunny *b, const QString& filename)
{
	if(!b->IsConnected())
		return false;

	// Get the record plugin folder path
	std::unique_ptr<QDir> httpDir(GetLocalHTTPFolder());
	if(!httpDir.get())
	{
		handleVoiceError(b, "Unable to access recording folder");
		return false;
	}

	// Build path to the recorded file (it's in the record plugin's folder)
	QString recordFolder = httpDir->absolutePath().replace("chatgpt", "record");
	QString wavFilePath = QDir(recordFolder).absoluteFilePath(filename);

	// Check if the file exists
	if(!QFile::exists(wavFilePath))
	{
		handleVoiceError(b, "Recording file not found");
		return false;
	}

	// Convert WAV to FLAC for Google Speech recognition
	QString flacFile = convertToFlac(wavFilePath);
	if(flacFile.isEmpty())
	{
		handleVoiceError(b, "Failed to convert audio format");
		return false;
	}

	// Send to speech recognition (asynchronous)
	// The recognizeSpeech method will trigger onSpeechRecognitionResponse
	recognizeSpeech(flacFile, b);
	
	// Clean up the temporary FLAC file
	QFile::remove(flacFile);

	return true;
}

QString PluginChatGPT::convertToFlac(const QString& wavFile)
{
	// Get our HTTP folder for temporary files
	std::unique_ptr<QDir> httpDir(GetLocalHTTPFolder());
	if(!httpDir.get())
	{
		return QString();
	}

	// Generate FLAC filename
	QFileInfo wavInfo(wavFile);
	QString flacFile = httpDir->absoluteFilePath(wavInfo.baseName() + ".flac");

	// Use sox to convert WAV to FLAC (same as voicecommand plugin)
	QString program = "/usr/bin/sox";
	QStringList arguments;
	arguments << wavFile << flacFile << "rate" << "16k";

	// Execute sox conversion
	QProcess process;
	process.start(program, arguments);
	if(!process.waitForFinished(10000)) // 10 second timeout
	{
		return QString();
	}

	// Check if conversion was successful
	if(process.exitCode() != 0 || !QFile::exists(flacFile))
	{
		return QString();
	}

	return flacFile;
}

QString PluginChatGPT::recognizeSpeech(const QString& flacFile, Bunny *b)
{
	// Read FLAC file
	QFile file(flacFile);
	if(!file.open(QIODevice::ReadOnly))
	{
		return QString();
	}

	QByteArray flacData = file.readAll();
	file.close();

	if(flacData.size() == 0)
	{
		return QString();
	}

	// Get Google Speech API key from environment
	QString apiKey = QString::fromLocal8Bit(qgetenv("GOOGLE_SPEECH_API_KEY"));
	if(apiKey.isEmpty())
	{
		apiKey = "AIzaSyAWY47hzyclccmabVobulOyH48U4Xo4LnE"; // Fallback to default key
	}

	// Build Google Speech API URL
	QString language = makeLanguage(b->GetLanguage());
	QUrl url = QString("http://www.google.com/speech-api/v2/recognize?lang=%1&key=%2&output=json")
			   .arg(language, apiKey);

	// Create network request
	QNetworkRequest request(url);
	request.setAttribute(QNetworkRequest::User, b->GetID());
	request.setRawHeader("Host", "www.google.com");
	request.setRawHeader("Content-Type", "audio/x-flac; rate=16000");
	request.setRawHeader("Keep-Alive", "300");
	request.setRawHeader("Connection", "keep-alive");

	// Send request using the speech network manager
	QNetworkReply* reply = speechNetworkManager->post(request, flacData);
	reply->setProperty("bunnyId", b->GetID());
	reply->setProperty("isForChatGPT", true);

	// The response will be handled by onSpeechRecognitionResponse
	// For now, we return empty - this is asynchronous
	return QString();
}

void PluginChatGPT::onSpeechRecognitionResponse(QNetworkReply* reply)
{
	QByteArray bunnyId = reply->property("bunnyId").toByteArray();
	bool isForChatGPT = reply->property("isForChatGPT").toBool();

	Bunny* bunny = BunnyManager::GetBunny(bunnyId);
	if(!bunny || !isForChatGPT)
	{
		reply->deleteLater();
		return;
	}

	if(reply->error() == QNetworkReply::NoError)
	{
		QString result = reply->readAll();
		QString recognizedText = parseGoogleSpeechResponse(result);

		if(!recognizedText.isEmpty())
		{
			// Send recognized text to ChatGPT
			if(!askChatGPTFromVoice(bunny, recognizedText))
			{
				handleVoiceError(bunny, "Failed to send question to ChatGPT");
			}
		}
		else
		{
			handleVoiceError(bunny, "Could not understand your question");
		}
	}
	else
	{
		handleVoiceError(bunny, "Speech recognition service unavailable");
	}

	reply->deleteLater();
}

QString PluginChatGPT::makeLanguage(const QString& lng)
{
	// Simple language mapping for Google Speech API
	// Based on the voicecommand plugin pattern but simplified for ChatGPT
	if(lng == "fr") return "fr-FR";
	if(lng == "en") return "en-US";
	if(lng == "de") return "de-DE";
	if(lng == "es") return "es-ES";
	if(lng == "it") return "it-IT";
	
	// Default fallback
	return "en-US";
}

QString PluginChatGPT::parseGoogleSpeechResponse(const QString& response)
{
	// Parse Google Speech API JSON response
	// Expected format: {"transcript":"text here","confidence":0.123}
	QRegExp rx("\\{\"transcript\":\"(.*)\",\"confidence\":(\\d+\\.\\d+)\\}");
	rx.setMinimal(true);
	
	if(rx.indexIn(response) != -1)
	{
		return rx.cap(1); // Return the transcript text
	}
	
	return QString(); // No valid transcript found
}