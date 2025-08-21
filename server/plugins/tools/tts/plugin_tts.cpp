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
	if(!b->IsConnected())
		return false;

	TTSManager::OutputFormat format = b->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;

	TTSAnswer sound = TTSManager::CreateSound(str, b->GetVoice(), b->GetLanguage(), format, false);
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
	if(!sayText(bunny,hRequest.GetArg("text")))
		return new ApiAnswers::Error(Translator::tr("Bunny '%1' is not connected", account).arg(QString(bunny->GetID())));
	return new ApiAnswers::Ok(Translator::tr("Sending '%1' to bunny '%2'", account).arg(hRequest.GetArg("text"), QString(bunny->GetID())));
}

QString PluginTTS::OnApiSay(Bunny *b, QVariant arg)
{
	sayText(b,arg.toString());
	return QString();
}
