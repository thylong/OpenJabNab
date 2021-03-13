#include "plugin_msgall.h"
#include "bunny.h"
#include "account.h"
#include "bunnymanager.h"
#include "translator.h"
#include "ttsmanager.h"
#include "messagepacket.h"

PluginMsgall::PluginMsgall():PluginInterface("msgall", "Send a message to all the bunnies connected on the server",SystemPlugin | MessagePlugin)
{
}

PluginMsgall::~PluginMsgall() {}

/*******
 * API *
 *******/

void PluginMsgall::InitApiCalls()
{
	DECLARE_PLUGIN_API_CALL("say()", &PluginMsgall::Api_Say);
}

PLUGIN_API_CALL(PluginMsgall::Api_Say)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied.");

	if(!hRequest.HasArg("text"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("text", GetName()));

	QString text = hRequest.GetArg("text");

	if(hRequest.HasArg("language"))
	{
		QString language = hRequest.GetArg("language");

		QList<QByteArray> BList = BunnyManager::GetConnectedBunniesList();
		foreach (QByteArray BID, BList)
		{
			Bunny *b = BunnyManager::GetBunny(this, BID);
			if(b->GetLanguage() == language)
			{
				TTSAnswer sound = TTSManager::CreateSound(text, b->GetVoice(), language);
				QByteArray fileName = sound.file.toLatin1();
				b->SendPacket(MessagePacket("MU " + fileName + "\nMW\n"), GetName());
				SaveMessage(b, QString(fileName));
			}
		}
	}
	else
	{
		QList<QByteArray> BList = BunnyManager::GetConnectedBunniesList();
		foreach (QByteArray BID, BList)
		{
			Bunny *b = BunnyManager::GetBunny(this, BID);
			TTSAnswer sound = TTSManager::CreateSound(text, b->GetVoice(), b->GetLanguage());
			QByteArray fileName = sound.file.toLatin1();
			b->SendPacket(MessagePacket("MU " + fileName + "\nMW\n"), GetName());
			SaveMessage(b, QString(fileName));
		}
	}
	return new ApiManager::ApiOk("Message sent.");
}
