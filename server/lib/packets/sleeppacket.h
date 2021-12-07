#ifndef _SLEEPPACKET_H_
#define _SLEEPPACKET_H_

#include <QByteArray>
#include "global.h"
#include "packet.h"

class OJN_EXPORT SleepPacket
	: public Packet
{
public:
	enum State { Wake_Up = 0, Sleep };

	SleepPacket(State);
	static SleepPacket * Parse(QByteArray const&);
	virtual ~SleepPacket() {};

	QByteArray GetPrintableData() const;
	Packet_Types GetType() const { return Packet::Packet_Sleep; }

	State GetState() const { return sleep ? Sleep : Wake_Up; };
	void SetState(State s) { sleep = (s == Sleep); }

protected:
	QByteArray GetInternalData() const;

private:
	SleepPacket() = delete;
	bool sleep;
};

#endif
