#include <QDateTime>
#include <QStringList>
#include "plugin_colorbreathing.h"
#include "ambientpacket.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "log.h"
#include "settings.h"
#include "translator.h"

PluginColorbreathing::PluginColorbreathing():PluginInterface("colorbreathing", "Change breathing color", BunnyV1Plugin | BunnyV2Plugin | ApiPlugin)
{
	availableColorsV2["none"]   = 0;
	availableColorsV2["blue"]   = 1;
	availableColorsV2["green"]  = 2;
	availableColorsV2["cyan"]   = 3;
	availableColorsV2["red"]    = 4;
	availableColorsV2["violet"] = 5;
	availableColorsV2["yellow"] = 6;
	availableColorsV2["white"]  = 7;

	availableColorsV1["none"]   = 0;
	availableColorsV1["red"]   = 1;
	availableColorsV1["green"]  = 2;
	availableColorsV1["yellow"]   = 3;
	availableColorsV1["blue"]    = 4;
	availableColorsV1["violet"] = 5;
	availableColorsV1["cyan"] = 6;
	availableColorsV1["white"]  = 7;
	availableColorsV1["orange"]  = 0xf;
}

/*
QString PluginColorbreathing::ChooseBytecode(Bunny * b)
{
	return "default_" + b->GetPluginSetting(GetName(), "color", QString("violet")).toString();
}
*/

void PluginColorbreathing::SetServices(Bunny * b)
{
	//enum Color { ColorOff = 0, ColorRed = 1, ColorGreen = 2, ColorYellow = 3, ColorBlue = 4, ColorPurple = 5, ColorCyan = 6, ColorWhite = 7, ColorPaleWhite = 8, ColorPaleRed = 9, ColorPaleGreen = 0xa, ColorPaleYellow = 0xb, ColorPaleBlue = 0xc, ColorPalePruple = 0xd, ColorPaleCyan = 0xe, ColorOrange = 0xf};
	b->SetService(9, availableColorsV1[b->GetPluginSetting(GetName(), "color", QString("violet")).toString()]);
}

QString PluginColorbreathing::OnApiSaveandset(Bunny *b, QVariant v)
{
	QString color = v.value<QString>();
	if(b->GetVersion() == 2)
	{
		if(availableColorsV2.contains(color))
		{
			b->SendPacket(AmbientPacket(AmbientPacket::Service_BottomLed, availableColorsV2[color]), GetName());
			b->SetPluginSetting(GetName(), "color", color);
			return QString("Color saved and changed");
		}
	}
	else if (b->GetVersion() == 1)
	{
		if(availableColorsV1.contains(color))
		{
			b->SetPluginSetting(GetName(), "color", color);
			return QString("Color saved and changed");
		}
	}
	return QString("Unknow color");
}

QString PluginColorbreathing::OnApiColor(Bunny *b, QVariant v)
{
	QString color = v.value<QString>();
	if(availableColorsV2.contains(color))
	{
		b->SendPacket(AmbientPacket(AmbientPacket::Service_BottomLed, availableColorsV2[color]), GetName());
		return QString("Color changed");
	}
	return QString("Unknow color");
}

void PluginColorbreathing::OnInitPacket(const Bunny * bunny, AmbientPacket & a, SleepPacket &)
{
	QString color = bunny->GetPluginSetting(GetName(), "color", QString("violet")).toString();
	a.SetServiceValue(AmbientPacket::Service_BottomLed, availableColorsV2[color]);
}

void PluginColorbreathing::InitApiCalls()
{
        DECLARE_PLUGIN_BUNNY_API_CALL("getColorList()", &PluginColorbreathing::Api_GetColorList);
        DECLARE_PLUGIN_BUNNY_API_CALL("setColor(name)", &PluginColorbreathing::Api_SetColor);
        DECLARE_PLUGIN_BUNNY_API_CALL("getColor()", &PluginColorbreathing::Api_GetColor);

        DECLARE_PLUGIN_BUNNY_API_CALL("color()", &PluginColorbreathing::Api_Color);
}

PLUGIN_BUNNY_API_CALL(PluginColorbreathing::Api_Color)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiList(availableColorsV2.keys());
	}
	else if(action == "get")
	{
        	return new ApiManager::ApiOk(bunny->GetPluginSetting(GetName(), "color", QString("violet")).toString());
	}
	else if(action == "set")
	{
		QString color = hRequest.GetArg("name");
		if(bunny->GetVersion() == 2)
		{
			if(availableColorsV2.contains(color))
			{
				bunny->SetPluginSetting(GetName(), "color", color);
				bunny->SendPacket(AmbientPacket(AmbientPacket::Service_BottomLed, availableColorsV2[color]), GetName());
				return new ApiManager::ApiOk(Translator::tr("Bottom color set to '%1'", account).arg(Translator::tr(color, account)));
			}
			return new ApiManager::ApiError(Translator::tr("Unknown '%1' color", account).arg(color));
		}
		else if(bunny->GetVersion() == 1)
		{
			if(availableColorsV1.contains(color))
			{
				bunny->SetPluginSetting(GetName(), "color", color);
				return new ApiManager::ApiOk(Translator::tr("Bottom color set to '%1'", account).arg(Translator::tr(color, account)));
			}
			return new ApiManager::ApiError(Translator::tr("Unknown '%1' color", account).arg(color));
		}
		else
		{
			return new ApiManager::ApiError(Translator::tr("Unknown bunny version", account));
		}
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginColorbreathing::Api_GetColor)
{
        Q_UNUSED(account);
	Q_UNUSED(hRequest);

        return new ApiManager::ApiOk(bunny->GetPluginSetting(GetName(), "color", QString("violet")).toString());
}

PLUGIN_BUNNY_API_CALL(PluginColorbreathing::Api_SetColor)
{
        QString color = hRequest.GetArg("name");
	if(bunny->GetVersion() == 2)
	{
        	if(availableColorsV2.contains(color))
        	{
        	        // Save new config
        	        bunny->SetPluginSetting(GetName(), "color", color);

			// Send color to bunny
			bunny->SendPacket(AmbientPacket(AmbientPacket::Service_BottomLed, availableColorsV2[color]), GetName());

			return new ApiManager::ApiOk(Translator::tr("Bottom color set to '%1'", account).arg(Translator::tr(color, account)));
		}
        	return new ApiManager::ApiError(Translator::tr("Unknown '%1' color", account).arg(color));
	}
	else if(bunny->GetVersion() == 1)
	{
        	if(availableColorsV1.contains(color))
        	{
        	        // Save new config
        	        bunny->SetPluginSetting(GetName(), "color", color);
			return new ApiManager::ApiOk(Translator::tr("Bottom color set to '%1'", account).arg(Translator::tr(color, account)));
		}
        	return new ApiManager::ApiError(Translator::tr("Unknown '%1' color", account).arg(color));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Unknown bunny version", account));
	}
}

PLUGIN_BUNNY_API_CALL(PluginColorbreathing::Api_GetColorList)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	if(bunny->GetVersion() == 1)
	{
		return new ApiManager::ApiList(availableColorsV1.keys());
	}
	else if(bunny->GetVersion() == 2)
	{
		return new ApiManager::ApiList(availableColorsV2.keys());
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Unknown bunny version", account));
	}
}

