#ifndef _PLUGINLEQUIPEINTERFACE_H_
#define _PLUGINLEQUIPEINTERFACE_H_

#include <QDateTime>
#include "translator.h"
#include "cron.h"

class PluginLequipeInterface
{
public:
	virtual void OnCron(Bunny *, QVariant);
protected:
	virtual QString makeDate(QDateTime);
	virtual QString stringDecode(QString);

	virtual void GetMatchs(bool, bool);
	virtual void GetMatchs(bool);
	virtual void GetMatchs();
	virtual void GetMatch(int, bool);
	virtual void GetMatch(int);

	QString codeSport;
	QString urlSport;
	QString empreinteSport;
	QString logSport;

	QStringList currentMatchs;
	QDateTime lastCheck;
};

#include "pluginlequipeinterface_inline.h"

Q_DECLARE_INTERFACE(PluginLequipeInterface,"org.toms.openjabnab.PluginLequipeInterface/1.0")

#endif
