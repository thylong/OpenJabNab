//	QString area;
//	QString location;
//	float stdOffset;
//	float dstOffset;
//	QString stdCode;
//	QString dstCode;
//	QString stdName;
//	QString dstName;
//	bool dst;
//	QString dstStart;
//	QString dstEnd;

inline void Translator::LoadTimeZones()
{
/*
	UTC.area = "UTC";
	UTC.stdOffset = 0;
	UTC.stdCode = "UTC";
	UTC.stdName = "Universal Time Coordinated";
	UTC.dst = false;

	QHash<QString, Timezone *> tzs = TimezoneManager::GetTimezonesList();
	QHash<QString, Timezone *>::iterator i;
	LogError("Timezones : ");
	for (i = tzs.begin(); i != tzs.end(); ++i)
	{
		LogError(i.key());
	}
*/
/*
	TimeZone EuropeParis;
	EuropeParis.area = "Europe";
	EuropeParis.location = "Paris";
	EuropeParis.stdOffset = 1;
	EuropeParis.dstOffset = 2;
	EuropeParis.stdCode = "CET";
	EuropeParis.dstCode = "CEST";
	EuropeParis.stdName = "Central European Time";
	EuropeParis.dstName = "Central European Summer Time";
	EuropeParis.dst = true;
	EuropeParis.dstStart = "last 7 of 3 at 2:00";
	EuropeParis.dstEnd = "last 7 of 10 at 3:00";
	timezones.insert("Europe/Paris", EuropeParis);

	TimeZone EuropeBucarest;
	EuropeBucarest.area = "Europe";
	EuropeBucarest.location = "Bucarest";
	EuropeBucarest.stdOffset = 2;
	EuropeBucarest.dstOffset = 3;
	EuropeBucarest.stdCode = "EEST";
	EuropeBucarest.dstCode = "EET";
	EuropeBucarest.stdName = "Eastern European Time";
	EuropeBucarest.dstName = "Eastern European Summer Time";
	EuropeBucarest.dst = true;
	EuropeBucarest.dstStart = "last 7 of 3 at 3:00";
	EuropeBucarest.dstEnd = "last 7 of 10 at 4:00";
	timezones.insert("Europe/Bucarest", EuropeBucarest);

	TimeZone EuropeMoscou;
	EuropeMoscou.area = "Europe";
	EuropeMoscou.location = "Moscou";
	EuropeMoscou.stdOffset = 4;
	EuropeMoscou.stdCode = "MSK";
	EuropeMoscou.stdName = "Moscow Standard Time";
	EuropeMoscou.dst = false;
	timezones.insert("Europe/Moscou", EuropeMoscou);

	TimeZone AfriqueReunion;
	AfriqueReunion.area = "Afrique";
	AfriqueReunion.location = "Reunion";
	AfriqueReunion.stdOffset = 4;
	AfriqueReunion.stdCode = "RET";
	AfriqueReunion.stdName = "Reunion Time";
	AfriqueReunion.dst = false;
	timezones.insert("Afrique/Reunion", AfriqueReunion);

	TimeZone NorthAmericaDenver;
	NorthAmericaDenver.area = "NorthAmerica";
	NorthAmericaDenver.location = "Denver";
	NorthAmericaDenver.stdOffset = -7;
	NorthAmericaDenver.dstOffset = -6;
	NorthAmericaDenver.stdCode = "MST";
	NorthAmericaDenver.dstCode = "MDT";
	NorthAmericaDenver.stdName = "Moutain Standard Time";
	NorthAmericaDenver.dstName = "Moutain Daylight Time";
	NorthAmericaDenver.dst = true;
	NorthAmericaDenver.dstStart = "second 7 of 3 at 2:00";
	NorthAmericaDenver.dstEnd = "first 7 of 11 at 2:00";
	timezones.insert("Europe/Bucarest", NorthAmericaDenver);

	QString tz = GlobalSettings::Get("Config/TimeZone", "UTC").toString();
	if(timezones.contains(tz))
		server = timezones.value(tz);
	else
		server = UTC;
*/
//	LogDebug(getPreviousDstStart("Afrique/Reunion").toString("ddd MMMM d yy hh:mm"));
}

