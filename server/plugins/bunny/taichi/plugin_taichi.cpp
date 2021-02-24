#include "plugin_taichi.h"
#include "ambientpacket.h"
#include "messagepacket.h"
#include "bunny.h"

PluginTaichi::PluginTaichi():PluginInterface("taichi", "Manage Bunny's Taichi",BunnyV2Plugin | BunnyV1Plugin | RfidPlugin)
{
}

PluginTaichi::~PluginTaichi() {}

void PluginTaichi::OnBunnyConnect(Bunny * b)
{
	SendTaichiFrequency(b);
}

void PluginTaichi::OnBunnyDisconnect(Bunny * b)
{
	if(b->IsConnected())
		b->SendPacket(AmbientPacket(AmbientPacket::Service_TaiChi,0), GetName());
}

void PluginTaichi::SetServices(Bunny * b)
{
	int frequency = b->GetPluginSetting(GetName(), "frequency", 0).toInt();
	if(frequency < 0)
		frequency = 0;
	if(frequency > 255)
		frequency = 255;
	b->SetService(14, frequency);
}

bool PluginTaichi::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence("taichi tai-chi gymnastique", command))
	{
		QByteArray message = "TA go\n";
		b->SendPacket(MessagePacket(message), GetName());
		return true;
	}
	return false;
}

void PluginTaichi::OnInitPacket(const Bunny * b, AmbientPacket & a, SleepPacket &)
{
	int frequency = b->GetPluginSetting(GetName(), "frequency", 0).toInt();
	if(frequency < 0)
		frequency = 0;
	if(frequency > 255)
		frequency = 255;
	a.SetServiceValue(AmbientPacket::Service_TaiChi,frequency);
}

void PluginTaichi::SendTaichiFrequency(Bunny * b)
{
	int frequency = b->GetPluginSetting(GetName(), "frequency", 0).toInt();
	if(frequency < 0)
		frequency = 0;
	if(frequency > 255)
		frequency = 255;
	b->SendPacket(AmbientPacket(AmbientPacket::Service_TaiChi,frequency), GetName());
}


QString PluginTaichi::OnApiTaichi(Bunny * b, QVariant)
{
	QByteArray message = "TA go\n";
	b->SendPacket(MessagePacket(message), GetName());
	return QString();
}

bool PluginTaichi::OnRFID(Bunny * b, QByteArray const& tag)
{
	QString rfid = b->GetPluginSetting(GetName(), "RFID", QString()).toString();
	if(rfid.toLatin1() == tag.toHex())
	{
		LogDebug("OnRFID Taichi");
		QByteArray message = "TA go\n";
		b->SendPacket(MessagePacket(message), GetName());
		return true;
	}
	return false;
}

/*******
 * API *
 *******/

void PluginTaichi::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("setFrequency(value)", PluginTaichi, Api_SetFrequency);
	DECLARE_PLUGIN_BUNNY_API_CALL("getFrequency()", PluginTaichi, Api_GetFrequency);
	DECLARE_PLUGIN_BUNNY_API_CALL("setRfid(tag)", PluginTaichi, Api_SetRFID);
}

PLUGIN_BUNNY_API_CALL(PluginTaichi::Api_SetFrequency)
{
	Q_UNUSED(account);

	bunny->SetPluginSetting(GetName(), "frequency", QVariant(hRequest.GetArg("value").toInt()));
	SendTaichiFrequency(bunny);
	return new ApiManager::ApiOk(QString("Plugin configuration updated."));
}

PLUGIN_BUNNY_API_CALL(PluginTaichi::Api_GetFrequency)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	return new ApiManager::ApiString(QString::number(bunny->GetPluginSetting(GetName(), "frequency", 0).toInt()));
}

PLUGIN_BUNNY_API_CALL(PluginTaichi::Api_SetRFID)
{
	Q_UNUSED(account);

	QString tag = hRequest.GetArg("tag");

	if(tag.length() > 0)
	{
		bunny->SetPluginSetting(GetName(), "RFID", tag);
		return new ApiManager::ApiOk(QString("RFID '%1' will now launch taichi for bunny '%2'").arg(tag, QString(bunny->GetID())));
	}
	else
	{
		bunny->RemovePluginSetting(GetName(), "RFID");
		return new ApiManager::ApiOk(QString("Remove RFID for bunny '%2'").arg(QString(bunny->GetID())));
	}
}

