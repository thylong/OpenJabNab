#include <memory>

#include <QDir>
#include <QCryptographicHash>

#include "plugin_tts.h"

#include "bunny.h"
#include "bunnymanager.h"
#include "packets/messagepacket.h"
#include "settings.h"
#include "translator.h"
#include "tts/ttsmanager.h"

PluginTTS::PluginTTS()
	: PluginInterface("tts", "TTS Plugin, Send Text to Bunny",
										BunnyV2Plugin | BunnyV1Plugin | ApiPlugin
									 )
{
}
/*******
 * API *
 *******/

void PluginTTS::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("say(text)", &PluginTTS::Api_Say);
}

bool PluginTTS::sayText(Bunny *b, const QString& str)
{
	return sayText(b, str, QString(), QString());
}

bool PluginTTS::sayText(Bunny *b, const QString& str, const QString& voice, const QString& language)
{
	if(!b->IsConnected())
		return false;

	TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;

	// Use provided voice/language or fall back to bunny defaults
	QString useVoice = voice.isEmpty() ? b->GetVoice() : voice;
	QString useLanguage = language.isEmpty() ? b->GetLanguage() : language;

	TTSAnswer sound = TTSManager::CreateSound(str, useVoice, useLanguage, format, false);
	TTSLog(b->GetID(), GetName(), sound);

	if(b->GetVersion() == 1)
	{
		AddSoundToSend(b, sound.file);
	}
	else
	{
		b->SendPacket(MessagePacket("MU " + sound.file.toLatin1() + "\nMW\n"), GetName());
	}
	return true;
}

PLUGIN_BUNNY_API_CALL(PluginTTS::Api_Say)
{
	QString text = hRequest.GetArg("text");
	QString voice = hRequest.GetArg("voice");
	QString language = hRequest.GetArg("language");
	
	if(!sayText(bunny, text, voice, language))
		return new ApiAnswers::Error(Translator::tr("Bunny '%1' is not connected", account).arg(QString(bunny->GetID())));
	
	QString msg = voice.isEmpty() ? 
		Translator::tr("Sending '%1' to bunny '%2'", account).arg(text, QString(bunny->GetID())) :
		Translator::tr("Sending '%1' to bunny '%2' with voice '%3'", account).arg(text, QString(bunny->GetID()), voice);
	
	return new ApiAnswers::Ok(msg);
}

QString PluginTTS::OnApiSay(Bunny *b, QVariant arg)
{
	sayText(b,arg.toString());
	return QString();
}
