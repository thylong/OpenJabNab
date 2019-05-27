#ifndef _CHORCONFIGPACKET_H_
#define _CHORCONFIGPACKET_H_

#include <QByteArray>
#include "global.h"
#include "packet.h"

class OJN_EXPORT ChorconfigPacket : public Packet
{
public:
	ChorconfigPacket(QByteArray const&);
	static ChorconfigPacket * Parse(QByteArray const&);
	virtual ~ChorconfigPacket() {};

	void SetChorconfig(QByteArray const& s);
	void AddChorconfig(QByteArray const& s);

	Packet_Types GetType() const;
	QByteArray GetPrintableData() const;
	QByteArray const& GetChorconfig() const;
	
protected:
	ChorconfigPacket();
	QByteArray GetInternalData() const;
	QByteArray chorconfig;
	
private:
	static const unsigned char inversion_table[];
};


inline void ChorconfigPacket::SetChorconfig(QByteArray const& s)
{
	chorconfig = s;
}

inline void ChorconfigPacket::AddChorconfig(QByteArray const& s)
{
	chorconfig += s;
}

inline Packet::Packet_Types ChorconfigPacket::GetType() const
{
	return Packet::Packet_Chorconfig;
}

inline QByteArray ChorconfigPacket::GetPrintableData() const
{
	return chorconfig;
}

inline QByteArray const& ChorconfigPacket::GetChorconfig() const
{
	return chorconfig;
}

#endif
