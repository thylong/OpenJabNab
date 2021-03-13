#include "plugin_ears.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "ambientpacket.h"
#include "translator.h"
#include "messagepacket.h"

PluginEars::PluginEars():PluginInterface("ears", "Ears Pairing with another Bunny",BunnyV2Plugin | EarsPlugin ) { }

PluginEars::~PluginEars() {}

bool PluginEars::OnEarsMove(Bunny * b, int l, int r) {
	/* Get Setting */
	QByteArray Friend = b->GetPluginSetting(GetName(), "Friend", "").toByteArray();
	QString FName(Friend);
	/* If setting set */
	if(!Friend.isEmpty())
	{
		Bunny *f = BunnyManager::GetBunny(this, Friend);
		/* If aFriend is found */
		if(f)
		{
			/* If this bunny is the Friend's friend */
			if(f->GetPluginSetting(GetName(), "Friend", "").toByteArray() == b->GetID())
			{
				/* If the friend is connected and not sleeping */
				if(f->IsConnected() && !f->IsSleeping())
				{
					/* Debug*/
					LogDebug(QString("Friend: %1 Ears to: %2 - %3").arg(FName).arg(l).arg(r));
					/* Send A packet to move the ears */
					AmbientPacket p;
					p.SetEarsPosition(l, r);
					f->SetGlobalSetting("EarLeft", l);
					f->SetGlobalSetting("EarRight", r);
					f->SendPacket(p, GetName());
				}
			}
		}
	}

	return true;
}

/*******/
/* API */
/*******/
void PluginEars::InitApiCalls()
{
	/* Basic API calls, Set and Get Friend's ID */
	DECLARE_PLUGIN_BUNNY_API_CALL("getFriend()", &PluginEars::Api_getFriend);
	DECLARE_PLUGIN_BUNNY_API_CALL("setFriend(id)", &PluginEars::Api_setFriend);
	DECLARE_PLUGIN_BUNNY_API_CALL("checkFriend(id)", &PluginEars::Api_checkFriend);

	DECLARE_PLUGIN_BUNNY_API_CALL("friend()", &PluginEars::Api_Friend);
}

PLUGIN_BUNNY_API_CALL(PluginEars::Api_Friend)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "check")
	{
		if(!hRequest.HasArg("id"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("id", GetName()));

		Bunny * f = BunnyManager::GetBunny(hRequest.GetArg("id").toLatin1());
		return new ApiManager::ApiString(f->GetPluginSetting(GetName(), "Friend", "").toString() == bunny->GetID() ? "Friend" : "Not friend");
	}
	else if(action == "get")
	{
		return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "Friend", "").toString());
	}
	else if(action == "set")
	{
		if(!hRequest.HasArg("id"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("id", GetName()));

		bunny->SetPluginSetting(GetName(), "Friend", QString(hRequest.GetArg("id")));
		return new ApiManager::ApiOk(Translator::tr("Bunny '%1' is now friend with bunny '%2'", account).arg(hRequest.GetArg("id"), QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginEars::Api_setFriend)
{
	Q_UNUSED(account);
	/* Update Configuration */
	bunny->SetPluginSetting(GetName(), "Friend", QVariant(hRequest.GetArg("id")));
	return new ApiManager::ApiOk(QString("Plugin configuration updated."));
}

PLUGIN_BUNNY_API_CALL(PluginEars::Api_checkFriend)
{
	Q_UNUSED(account);
	Bunny * f = BunnyManager::GetBunny(hRequest.GetArg("id").toLatin1());
	return new ApiManager::ApiString(f->GetPluginSetting(GetName(), "Friend", "").toString() == bunny->GetID() ? "Friend" : "Not friend");
}

PLUGIN_BUNNY_API_CALL(PluginEars::Api_getFriend)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);
	/* Get Configuration */
	return new ApiManager::ApiString(bunny->GetPluginSetting(GetName(), "Friend", "").toString());
}
