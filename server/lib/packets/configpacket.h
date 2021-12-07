#ifndef _CONFIGPACKET_H_
#define _CONFIGPACKET_H_

#include <QByteArray>
#include <QString>
#include "global.h"
#include "packet.h"

class OJN_EXPORT ConfigPacket : public Packet
{
public:
	ConfigPacket() {};
	ConfigPacket(QString);
	static ConfigPacket * Parse(QByteArray const&);
	virtual ~ConfigPacket() {};

	QByteArray GetPrintableData() const;
	Packet_Types GetType() const;

	QString GetConfig() const;
	void SetConfig(QString);
	
protected:
	QByteArray GetInternalData() const;
	QString config;

};

inline Packet::Packet_Types ConfigPacket::GetType() const
{
	return Packet::Packet_Config;
}

inline QString ConfigPacket::GetConfig() const
{
	return config;
}

inline void ConfigPacket::SetConfig(QString s)
{
	config = s;
}

#endif
