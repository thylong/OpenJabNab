inline QString PluginLequipeInterface::makeDate(QDateTime date)
{
	QLocale english = QLocale(QLocale::English, QLocale::UnitedStates);
	return english.toString(date, "ddd, d MMM yyyy hh:mm:ss ") + "GMT";
}

inline QString PluginLequipeInterface::stringDecode(QString str)
{
	QRegExp rx("(\\\\u[0-9a-fA-F]{4})");
	int pos = 0;
	while ((pos = rx.indexIn(str, pos)) != -1)
	{
	    str.replace(pos++, 6, QChar(rx.cap(1).right(4).toUShort(0, 16)));
	}
	str.replace("\\", "");
	return str;
}

inline void PluginLequipeInterface::OnCron(Bunny * , QVariant)
{
	bool fetch = false;
	QDateTime now = QDateTime::currentDateTime();
	foreach(QString match, GetSettings("Matchs/List", QStringList()).toStringList())
	{
		QString date = GetSettings("Matchs/Time_" + match, QString()).toString();
		if(date != QString())
		{
			QDateTime start = QDateTime::fromTime_t(date.toInt());
			if(now.secsTo(start) <= 5*60 && now.secsTo(start) >= -180 * 60)
				fetch = true;
		}
	}
	if(fetch)
	{
		LogDebug(QString("Need to check %1 matchs").arg(logSport));
		GetMatchs();
	}
	else if(now.toString("m").toInt() % 30 == 0)
	{
		LogDebug(QString("Just a quick check on %1 matchs").arg(logSport));
		GetMatchs();
	}
}

