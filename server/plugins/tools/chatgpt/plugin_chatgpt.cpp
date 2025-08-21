#include <memory>

#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QNetworkRequest>

#include "plugin_chatgpt.h"

#include "bunny.h"
#include "bunnymanager.h"
#include "packets/messagepacket.h"
#include "settings.h"
#include "translator.h"
#include "tts/ttsmanager.h"

PluginChatGPT::PluginChatGPT()
	: PluginInterface("chatgpt", "ChatGPT Plugin, Send questions to ChatGPT",
					  BunnyV2Plugin | BunnyV1Plugin | ApiPlugin
					 )
{
	networkManager = new QNetworkAccessManager(this);
	connect(networkManager, &QNetworkAccessManager::finished, this, &PluginChatGPT::onChatGPTResponse);
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
	QString jsonData = buildJsonRequest(question);

	// Store bunny ID for response handling
	QNetworkReply* reply = networkManager->post(request, jsonData.toUtf8());
	reply->setProperty("bunnyId", b->GetID());
	reply->setProperty("question", question);

	return true;
}

QString PluginChatGPT::buildJsonRequest(const QString& message)
{
	QJsonObject json;
	json["model"] = "gpt-3.5-turbo";
	json["temperature"] = 0.7;
	json["max_tokens"] = 150;

	QJsonArray messages;
	QJsonObject systemMessage;
	systemMessage["role"] = "system";
	systemMessage["content"] = "You are a helpful assistant. Keep responses concise and suitable for speech output.";
	messages.append(systemMessage);

	QJsonObject userMessage;
	userMessage["role"] = "user";
	userMessage["content"] = message;
	messages.append(userMessage);

	json["messages"] = messages;

	return QJsonDocument(json).toJson(QJsonDocument::Compact);
}

void PluginChatGPT::onChatGPTResponse(QNetworkReply* reply)
{
	QByteArray bunnyId = reply->property("bunnyId").toByteArray();
	QString question = reply->property("question").toString();
	
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
		// Handle error - could log or send error message via TTS
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