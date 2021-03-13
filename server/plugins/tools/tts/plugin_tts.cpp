#include <QDir>
#include <QCryptographicHash>
#include <memory>
#include "plugin_tts.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "httprequest.h"
#include "messagepacket.h"
#include "settings.h"
#include "translator.h"
#include "ttsmanager.h"

PluginTTS::PluginTTS():PluginInterface("tts", "TTS Plugin, Send Text to Bunny",BunnyV2Plugin | BunnyV1Plugin)
{
}
/*******
 * API *
 *******/

void PluginTTS::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("say(text)", &PluginTTS::Api_Say);
}

PLUGIN_BUNNY_API_CALL(PluginTTS::Api_Say)
{
	if(!bunny->IsConnected())
		return new ApiManager::ApiError(Translator::tr("Bunny '%1' is not connected", account).arg(QString(bunny->GetID())));

	TTSManager::OutputFormat format = bunny->GetVersion() == 1 ? TTSManager::Format_Adp : TTSManager::Format_Mp3;

	TTSAnswer sound = TTSManager::CreateSound(hRequest.GetArg("text"), bunny->GetVoice(), bunny->GetLanguage(), format, false);
	TTSLog(bunny->GetID(), GetName(), sound);

	if(bunny->GetVersion() == 1)
	{
		AddSoundToSend(bunny, sound.file);
	}
	else
	{
		bunny->SendPacket(MessagePacket("MU " + sound.file.toLatin1() + "\nMW\n"), GetName());
	}
	return new ApiManager::ApiOk(Translator::tr("Sending '%1' to bunny '%2'", account).arg(hRequest.GetArg("text"), QString(bunny->GetID())));
}
