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

#include "plugin_chatgpt.h"

#include "bunny.h"
#include "bunnymanager.h"
#include "account.h"
#include "accountmanager.h"
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

bool PluginChatGPT::startVoiceRecording(Bunny *b)
{
	if(!b->IsConnected())
		return false;

	// Check user permissions (VIP/Premium/Admin required for voice)
	if(!checkUserPermissions(b))
	{
		handleVoiceError(b, "Voice commands require VIP, Premium, or Admin account");
		return false;
	}

	// Play recording prompt
	playRecordingPrompt(b);

	// TODO: Implement actual voice recording trigger
	// For now, return true to indicate we handled the click
	return true;
}

bool PluginChatGPT::checkUserPermissions(Bunny *b)
{
	// Reuse logic from voicecommand plugin
	Account * a = AccountManager::GetAccountByLogin(b->GetGlobalSetting("OwnerAccount").toByteArray());
	if(a != NULL)
	{
		if(a->IsVip() || a->IsAdmin() || a->IsPremium())
		{
			if(!a->GetAbuse())
			{
				return true;
			}
		}
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

// Placeholder methods for Phase 2 implementation
bool PluginChatGPT::processVoiceRecording(Bunny *b, const QString& filename)
{
	// TODO: Implement in Phase 2
	return false;
}

QString PluginChatGPT::convertToFlac(const QString& wavFile)
{
	// TODO: Implement in Phase 2
	return QString();
}

QString PluginChatGPT::recognizeSpeech(const QString& flacFile, Bunny *b)
{
	// TODO: Implement in Phase 3
	return QString();
}

void PluginChatGPT::onSpeechRecognitionResponse(QNetworkReply* reply)
{
	// TODO: Implement in Phase 3
	reply->deleteLater();
}