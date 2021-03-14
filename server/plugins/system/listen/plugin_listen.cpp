#include "plugin_listen.h"
#include "ambientpacket.h"
#include "messagepacket.h"
#include "bunny.h"
#include "translator.h"

PluginListen::PluginListen():PluginInterface("listen", "Manage Bunny's availability to ear sounds",SystemPlugin)
{
}

PluginListen::~PluginListen() {}

void PluginListen::OnBunnyConnect(Bunny * b)
{
	SendListeningGain(b);
}

bool PluginListen::XmppBunnyMessage(Bunny * b, QByteArray const& data)
{
	if(b->GetPluginSetting(GetName(), "gain", 0).toInt())
	{
		QRegExp rx("<message[^>]*><sound xmlns=\"violet:nabaztag:sound:([^\"]*)\"><volume>(\\d+)</volume></sound></message>");
		rx.setMinimal(true);
		if (rx.indexIn(data) != -1)
		{
			QString status = rx.cap(1);
			int volume = rx.cap(2).toInt();

			LogDebug("Bunny " + QString(b->GetID()) + " listen for a sound (volume = " + QString::number(volume) + ")");

			if(listenList.contains(b->GetID()))
			{
				QDateTime now = QDateTime::currentDateTime();
				ListenElement previous = listenList.value(b->GetID());
				if(previous.date.secsTo(now) <= 60)
				{
					listenList.remove(b->GetID());
					LogDebug("Bunny " + QString(b->GetID()) + " has ear another sound less than 60sec ago");
					return b->OnListen(volume);
				}
				else
				{
					LogDebug("Bunny " + QString(b->GetID()) + " does not ear another sound in the past 60sec");
				}
			}
			ListenElement e;
			e.date = QDateTime::currentDateTime();
			e.bunny = b;
			e.volume = volume;
			listenList.insert(b->GetID(), e);
		}
	}
	return false;
}

void PluginListen::OnInitPacket(const Bunny * b, AmbientPacket & a, SleepPacket &)
{
	int gain = b->GetPluginSetting(GetName(), "gain", 0).toInt();
	if(gain < 0)
		gain = 0;
	if(gain > 255)
		gain = 255;
	a.SetServiceValue(AmbientPacket::Service_Listen, gain);
}

void PluginListen::SendListeningGain(Bunny * b)
{
	int gain = b->GetPluginSetting(GetName(), "gain", 0).toInt();
	if(gain < 0)
		gain = 0;
	if(gain > 255)
		gain = 255;
	b->SendPacket(AmbientPacket(AmbientPacket::Service_Listen, gain), GetName());
}

/*******
 * API *
 *******/

void PluginListen::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("config()", &PluginListen::Api_Config);
}

PLUGIN_BUNNY_API_CALL(PluginListen::Api_Config)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "set")
	{
		if(!hRequest.HasArg("value"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("value", GetName()));

		bunny->SetPluginSetting(GetName(), "gain", QVariant(hRequest.GetArg("value").toInt()));
		SendListeningGain(bunny);
		return new ApiAnswers::Ok(QString("Plugin configuration updated."));
	}
	else if(action == "get")
	{
		return new ApiAnswers::String(QString::number(bunny->GetPluginSetting(GetName(), "gain", 0).toInt()));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

