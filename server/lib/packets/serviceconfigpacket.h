#ifndef _SERVICECONFIGPACKET_H_
#define _SERVICECONFIGPACKET_H_

#include <QByteArray>
#include "global.h"
#include "packet.h"

class OJN_EXPORT ServiceconfigPacket : public Packet
{
public:
	ServiceconfigPacket(QByteArray const&);
	static ServiceconfigPacket * Parse(QByteArray const&);
	virtual ~ServiceconfigPacket() {};

	void SetServiceconfig(QByteArray const& s);
	void AddServiceconfig(QByteArray const& s);

	Packet_Types GetType() const;
	QByteArray GetPrintableData() const;
	QByteArray const& GetServiceconfig() const;
	
protected:
	ServiceconfigPacket();
	QByteArray GetInternalData() const;
	QByteArray serviceconfig;
	
private:
	static const unsigned char inversion_table[];
};


inline void ServiceconfigPacket::SetServiceconfig(QByteArray const& s)
{
	serviceconfig = s;
}

inline void ServiceconfigPacket::AddServiceconfig(QByteArray const& s)
{
	serviceconfig += s;
}

inline Packet::Packet_Types ServiceconfigPacket::GetType() const
{
	return Packet::Packet_Serviceconfig;
}

inline QByteArray ServiceconfigPacket::GetPrintableData() const
{
	return serviceconfig;
}

inline QByteArray const& ServiceconfigPacket::GetServiceconfig() const
{
	return serviceconfig;
}

#endif
