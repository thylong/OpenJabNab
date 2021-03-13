#include "plugin_packet.h"
#include "account.h"
#include "bunny.h"
#include "ambientpacket.h"
#include "messagepacket.h"

PluginPacket::PluginPacket():PluginInterface("packet", "Send raw packets to bunny",BunnyV2Plugin) {}

void PluginPacket::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("sendAmbient(service,value)", &PluginPacket::Api_SendAmbient);
	DECLARE_PLUGIN_BUNNY_API_CALL("sendPacket(data)", &PluginPacket::Api_SendPacket);
	DECLARE_PLUGIN_BUNNY_API_CALL("sendMessage(msg)", &PluginPacket::Api_SendMessage);
	DECLARE_PLUGIN_BUNNY_API_CALL("sendExpert(msg)", &PluginPacket::Api_SendExpert);
	DECLARE_PLUGIN_API_CALL("sendMessage(msg)", &PluginPacket::Api_SendServerMessage);
}

PLUGIN_BUNNY_API_CALL(PluginPacket::Api_SendAmbient)
{
	Q_UNUSED(account);

	AmbientPacket p;
	p.SetServiceValue((AmbientPacket::Services)hRequest.GetArg("service").toInt(), hRequest.GetArg("value").toInt());
	bunny->SendPacket(p, GetName());
	return new ApiManager::ApiOk(QString("Service sent to bunny"));
}

PLUGIN_BUNNY_API_CALL(PluginPacket::Api_SendPacket)
{
	Q_UNUSED(account);

	QByteArray data = QByteArray::fromHex(hRequest.GetArg("data").toLatin1());
	bunny->SendData(data);
	return new ApiManager::ApiOk(QString("'%1' sent to bunny").arg(QString(data.toHex())));
}

PLUGIN_BUNNY_API_CALL(PluginPacket::Api_SendExpert)
{
	Q_UNUSED(account);

	QByteArray data = hRequest.GetArg("msg").toLatin1();
	bunny->SendExpertData(data);
	return new ApiManager::ApiOk(QString("'%1' sent to bunny").arg(QString(data)));
}

PLUGIN_BUNNY_API_CALL(PluginPacket::Api_SendMessage)
{
	Q_UNUSED(account);

	QByteArray msg = hRequest.GetArg("msg").toLatin1();
	bunny->SendPacket(MessagePacket(msg), GetName());
	return new ApiManager::ApiOk(QString("'%1' sent to bunny").arg(QString(msg)));
}

PLUGIN_API_CALL(PluginPacket::Api_SendServerMessage)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError("Access denied.");

	int limit = 0;
	if(hRequest.HasArg("limit"))
		limit = hRequest.GetArg("limit").toInt();

	int repeat = 1;
	if(hRequest.HasArg("repeat"))
		repeat = hRequest.GetArg("repeat").toInt();

	QString message = hRequest.GetArg("msg");
	int count = 0;
	while(repeat > 0)
	{
		count = 0;
		QList<QByteArray> BList = BunnyManager::GetConnectedBunniesList();
		foreach (QByteArray BID, BList)
		{
			if(count < limit)
			{
				Bunny *b = BunnyManager::GetBunny(BID);
				QString msg = message;
				if(hRequest.HasArg("replace"))
				{
					msg = msg.replace("BUNNYMAC", QString(b->GetID())).replace("TIME", QString::number(QDateTime::currentDateTime().toTime_t()));
				}
				b->SendPacket(MessagePacket(msg.toLatin1()), GetName());
				count++;
			}
		}
		repeat--;
	}
	return new ApiManager::ApiOk(QString("'%1' sent to %2 bunnies").arg(QString(message), QString::number(count)));
}
