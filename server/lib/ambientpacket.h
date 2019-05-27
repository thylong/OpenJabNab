#ifndef _AMBIENTPACKET_H_
#define _AMBIENTPACKET_H_

#include <QByteArray>
#include <QMap>
#include "global.h"
#include "packet.h"

class OJN_EXPORT AmbientPacket : public Packet
{
public:
	//enum Services { Disable_Service = 0, Service_Weather, Service_StockMarket, Service_Periph, MoveLeftEar, MoveRightEar, Service_EMail, Service_AirQuality, Service_Nose, Service_BottomLed , Service_SoundVol, Service_Custom, Service_TaiChi = 0x0e, Service_Debug = 0x11, Service_Listen = 0x12};
	enum Services { Disable_Service = 0, Service_Weather, Service_StockMarket, Service_Periph, MoveLeftEar, MoveRightEar, Service_EMail, Service_AirQuality, Service_Nose, Service_Custom1, Service_Custom2, Service_Custom3, Service_Custom4, Service_Custom5, Service_Custom6, Service_Custom7, Service_Custom8, Service_Custom9, Service_Custom10, Service_BottomLed = 0x21 , Service_SoundVol = 0x22, Service_TaiChi = 0x23, Service_Debug = 0x25, Service_Listen = 0x26, Service_DisableRfid = 0x27};
	enum Weather_Values { Weather_Sun = 0, Weather_Cloudy, Weather_Smog, Weather_Rain, Weather_Snow, Weather_Storm};
	enum StockMarket_Values { StockMarket_HighDown = 0, StockMarket_MediumDown, StockMarket_LittleDown, StockMarket_Stable, StockMarket_LittleUp, StockMarket_MediumUp, StockMarket_HighUp};
	enum Periph_Values { Periph_VeryLow = 0, Periph_Low, Periph_LowAverage, Periph_Average, FastAverage, Fast, VeryFast };
	enum EMail_Values { EMail_No = 0, EMail_1, EMail_2, EMail_3AndMore };
	enum AirQuality_Values { AirQuality_Good = 0, AirQuality_Medium = 5, AirQuality_Bad = 10 };
	enum Nose_Values { Nose_No = 0, Nose_Blink, Nose_DoubleBlink };
	enum Debug_Values { Debug_Disabled = 0, Debug_Enabled };
	enum Custom_Values { Custom1 = 0, Custom2, Custom3, Custom4, Custom5, Custom6, Custom7, Custom8, Custom9, Custom10 };

	AmbientPacket() {};
	AmbientPacket(enum Services, unsigned char value);
	static AmbientPacket * Parse(QByteArray const&);
	virtual ~AmbientPacket() {};

	void SetServiceValue(enum Services, unsigned char);
	void SetEarsPosition(unsigned char, unsigned char);
	void DisableService(enum Services);

	Packet_Types GetType() const { return Packet::Packet_Ambient; };
	QByteArray GetPrintableData() const;
	QMap<unsigned char, unsigned char> const& GetServices() { return services; };
	
protected:
	QByteArray GetInternalData() const;
	QMap<unsigned char, unsigned char> services;
};

#endif
