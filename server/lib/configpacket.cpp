#include <QString>
#include "configpacket.h"

ConfigPacket::ConfigPacket(QString s)
{
	SetConfig(s);
}

ConfigPacket * ConfigPacket::Parse(QByteArray const& buffer)
{
	if(buffer.size() !=1)
		throw QString("Bad ConfigPacket size : %1").arg(QString(buffer.toHex()));

	unsigned char value = (unsigned char)buffer.at(0);
	if (value > 1)
		throw QString("Bad ConfigPacket value : %1").arg(QString(buffer.toHex()));

	ConfigPacket * s = new ConfigPacket;
	s->config = value;
	return s;
}

QByteArray ConfigPacket::GetInternalData() const
{
	return config.toLatin1();
}

QByteArray ConfigPacket::GetPrintableData() const
{
	return ("Config : " + config).toLatin1();
}
