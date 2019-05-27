#ifndef _SLEEPTIME_H_
#define _SLEEPTIME_H_

struct SleepTime {
        QTime sleepAt;
        int sleepOn;
        QTime wakeAt;
        int wakeOn;

	int WakeOn() {
		return (wakeOn < sleepOn || (wakeOn == sleepOn && wakeAt < sleepAt)) ? wakeOn + 7 : wakeOn;
	}
	bool valid() {
		if(sleepAt.isNull())
			return false;
		else if(wakeAt.isNull())
			return false;
		else if(wakeAt == sleepAt && wakeOn == sleepOn)
			return false;
		return true;
	}

	bool operator<(const SleepTime& sleep) const {
		if(sleepOn == sleep.sleepOn)
		{
			if(sleepAt == sleep.sleepAt)
			{
				if(wakeOn == sleep.wakeOn)
				{
					return wakeAt < sleep.wakeAt;
				}
				return wakeOn < sleep.wakeOn;
			}
			return sleepAt < sleep.sleepAt;
		}
		return sleepOn < sleep.sleepOn;
	}
 
	bool operator==(const SleepTime& sleep) const {
		return sleepAt == sleep.sleepAt && sleepOn == sleep.sleepOn && wakeAt == sleep.wakeAt && wakeOn == sleep.wakeOn ;
	}

	bool overlap(const SleepTime& sleep) const {
		SleepTime s = extended();
		return s.overlap(sleep) || sleep.overlap(s);
	}

	bool overlapSub(const SleepTime& sleep) const {
		SleepTime s1 = extended();
		SleepTime s2 = sleep.extended();
		if(s2.isExtended() && !s1.isExtended()) {
			s1 = s1.offseted();
		}
		if(s1.sleepOn > s2.sleepOn || (s1.sleepOn == s2.sleepOn && s1.sleepAt > s2.sleepAt) ) {
			if(s1.sleepOn < s2.wakeOn || (s1.sleepOn == s2.wakeOn && s1.sleepAt < s2.wakeAt) ) {
				return true;
			}
		}
		return false;
	}

	bool includedIn(const SleepTime& sleep) const {
		SleepTime s1 = extended();
		SleepTime s2 = sleep.extended();
		if(s2.isExtended() && !s1.isExtended()) {
			s1 = s1.offseted();
		}
		if(s1.sleepOn > s2.sleepOn || (s1.sleepOn == s2.sleepOn && s1.sleepAt > s2.sleepAt) ) {
			if(s1.wakeOn < s2.wakeOn || (s1.wakeOn == s2.wakeOn && s1.wakeAt < s2.wakeAt) ) {
				return true;
			}
		}
		return false;
	}

	SleepTime offseted() const {
		SleepTime s;
		s.sleepAt = sleepAt;
		s.sleepOn = sleepOn + 7;
		s.wakeAt = wakeAt;
		s.wakeOn = wakeOn + 7;

		return  s;
	}

	SleepTime extended() const {
		SleepTime s;
		s.sleepAt = sleepAt;
		s.sleepOn = sleepOn;
		s.wakeAt = wakeAt;
		s.wakeOn = wakeOn;

		if(wakeOn < sleepOn || (wakeOn == sleepOn && wakeAt < sleepAt) ) {
			s.wakeOn += 7;
		}
		return  s;
	}

	bool isExtended() const {
		return wakeOn > 7;
	}

	QString toString() {
		QString string;
		if(valid())
		{
			string = sleepAt.toString("hh:mm") + "|" + QString::number(sleepOn) + "|";
			string += wakeAt.toString("hh:mm") + "|" + QString::number(wakeOn);
		}
		return string;
	}

	bool IsAfter(QDateTime ) {
		return false;
	}

	bool IsBefore(QDateTime ) {
		return true;
	}

	bool IsSleepingAt(QDateTime date) {
		int day = date.date().dayOfWeek();
		QTime time = date.time();

		SleepTime sleep = extended();
		if(
			(sleep.sleepOn < day || (sleep.sleepOn == day && sleep.sleepAt < time) ) 
			&& (sleep.wakeOn > day || (sleep.wakeOn == day && sleep.wakeAt > time) ) 
		)
		{
			return true;
		}
		day += 7;
		if(
			(sleep.sleepOn < day || (sleep.sleepOn == day && sleep.sleepAt < time) ) 
			&& (sleep.wakeOn > day || (sleep.wakeOn == day && sleep.wakeAt > time) ) 
		)
		{
			return true;
		}
		return false;
	}
//	static function fromString();
}; 

#endif
