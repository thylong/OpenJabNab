#include <QDateTime>
#include <QTime>
#include <QTimer>

#include "cron.h"
#include "plugininterface.h"
//#include "cronlog.h"
#include "log.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "translator.h"

Cron::Cron() {
	LogInfo("Cron Started...");
	// Conmpute next slot
	int now = QDateTime::currentDateTime().toTime_t();
	QTimer::singleShot(1000 * (60 - (now%60)), this, SLOT(OnTimer()));
	lastGivenID = 0;
}

void Cron::OnTimer()
{
	unsigned int now = QDateTime::currentDateTime().toTime_t();

	// Find elements to run
	while(!CronElements.empty() && (CronElements.front().next_run <= now))
	{
		CronElement e = CronElements.front();
		CronElements.pop_front();

		if(e.callback)
		{
			if(e.bunny != NULL)
			{
				LogCron(e.plugin->GetName() + "::" + e.callback, e.bunny->GetID());
				//CronLog::Log("BC", e.plugin->GetName() + "::" + e.callback);
				e.bunny->SetGlobalSetting("LastCron", QString("%1 - %2->%3").arg(QDateTime::currentDateTime().toString("dd/MM/yyyy hh:mm:ss"), e.plugin->GetName(), e.callback));
			}
			else
			{
				LogCron(e.plugin->GetName() + "::" + e.callback);
				//CronLog::Log("-C", e.plugin->GetName() + "::" + e.callback);
			}
			QMetaObject::invokeMethod(e.plugin, e.callback, Q_ARG(Bunny*, e.bunny), Q_ARG(QVariant, e.data), Q_ARG(unsigned int, e.type));
		}
		else
		{
			if(e.bunny != NULL)
			{
				LogCron(e.plugin->GetName() + "::OnCron", e.bunny->GetID());
				//CronLog::Log("B-", e.plugin->GetName());
				e.bunny->SetGlobalSetting("LastCron", QString("%1 - %2->OnCron").arg(QDateTime::currentDateTime().toString("dd/MM/yyyy hh:mm:ss"), e.plugin->GetName()));
			}
			else
			{
				LogCron(e.plugin->GetName() + "::OnCron");
				//CronLog::Log("--", e.plugin->GetName());
			}
			e.plugin->OnCron(e.bunny, e.data, e.type);
		}

		if(e.interval != 0)
		{
			e.next_run += e.interval;
			AddCron(e);
		}
	}

	// Compute next slot
	now = QDateTime::currentDateTime().toTime_t();
	QTimer::singleShot(1000 * (60 - (now%60)), this, SLOT(OnTimer()));
}

std::list<CronElement> Cron::ListAllCron()
{
	return Instance().CronElements;
}

std::list<CronElement> Cron::ListAllBunnyCron(Bunny * b)
{
/*
	std::list<CronElement> list;
	QMutableLinkedListIterator<CronElement> i(Instance().CronElements);
	while(i.hasNext()) // Find position
	{
		CronElement e = i.next();
		if(e.bunny != NULL && b->GetID() == e.bunny->GetID())
		{
			list.insert(&e);
		}
	}
*/

	std::list<CronElement> list = Instance().CronElements;
	std::list<CronElement>::iterator i = list.begin();
	while (i != list.end()) {
		if((*i).bunny == NULL || b->GetID() != (*i).bunny->GetID())
			i = list.erase(i);
		else
			++i;
	}
/*
	std::list<CronElement>::iterator i;
	for (i = list.begin(); i != list.end(); ++i)
	{
		if((*i).bunny == NULL || b->GetID() != (*i).bunny->GetID())
		{

			list.insert(&(*i));
		}
	}
*/
	return list;
}

QMap<PluginInterface *, QDateTime> Cron::ListBunnyCron(Bunny * b)
{
	QMap<PluginInterface *, QDateTime> list;
	for (auto i = Instance().CronElements.begin(); i != Instance().CronElements.end(); ++i)
	{
		if((*i).bunny != NULL && b->GetID() == (*i).bunny->GetID())
		{
			list.insertMulti((*i).plugin, QDateTime::fromTime_t((*i).next_run));
		}
	}
	return list;
}

QTime Cron::mkTime(QString s)
{
	QTime t = QTime::fromString(s, "hh:mm");
	if(t.isValid())
		return t;
	t = QTime::fromString(s.replace("h", ":"), "hh:mm");
	if(t.isValid())
		return t;
	t = QTime::fromString(s.replace("H", ":"), "hh:mm");
	if(t.isValid())
		return t;
	return QTime();
}

void Cron::LogDebugCron(CronElement const& e)
{
	if(e.bunny->GetGlobalSetting("CronDebug", false).toBool())
	{
		QString bunny = QString(e.bunny->GetID());
		QString caller = "";
		if(e.plugin != NULL)
		{
			caller = e.plugin->GetName();
		}
		else
		{
			caller = "system";
		}
		if(e.callback != NULL)
		{
			caller += "::" + QString(e.callback);
		}
		else
		{
			caller += "::onCron";
		}
		caller += "(" + e.data.toString() + ")";
		QString time = QDateTime::fromTime_t(e.next_run).toString("yyyy-MM-dd hh:mm:ss");
		if(e.interval > 0)
		{
			time += " (" + QString::number(e.interval) + "s)";
		}

		LogCron(QString("Bunny %1 - Schedule %2 on %3").arg(bunny, caller, time));
	}
}

void Cron::AddCron(CronElement const& e)
{
	if(e.bunny != NULL)
		LogDebugCron(e);
	auto i = CronElements.begin();
	while(i != CronElements.end() && i->next_run < e.next_run) // Find position
		i++;
	CronElements.insert(i,e);
}

unsigned int Cron::Register(PluginInterface * p, unsigned int interval, unsigned int offsetH, unsigned int offsetM, Bunny * b, unsigned int type, QVariant data, const char * callback)
{
	if(interval > 24*60)
	{
		LogError("Cron : Interval should be <= 1 day");
		return 0;
	}
	if(!interval)
	{
		LogError("Cron : Interval should be >= 1 minute");
		return 0;
	}
	if(!p)
	{
		LogError("Cron : pointer is null !");
		return 0;
	}

	Cron & theCron = Instance();
	unsigned id = ++theCron.lastGivenID;
	if(!id)
		LogError("Warning Cron::Register : lastGivenID overlapped !");

	CronElement e;
	e.interval = interval * 60;
	e.callback = callback;
	e.plugin = p;
	e.bunny = b;
	e.data = data;
	e.id = id;
	e.type = type;

	// Compute next run
	QDateTime now = QDateTime::currentDateTime();
	QDateTime time = now;
	//time.addDays(-1);
	if(b != NULL)
	{
		time.setTime(Translator::MakeServerTime(b->GetGlobalSetting("TimeZone","UTC").toString(), QTime(offsetH, offsetM)));
		while(time < now)
			time = time.addSecs(interval*60);
	}
	else
	{
		time.setTime(QTime(offsetH, offsetM));
		while(time < now)
			time = time.addSecs(interval*60);
	}
	e.next_run = time.toTime_t();
	theCron.AddCron(e);

	//LogInfo(QString("Cron Register : %1 - %2").arg(p->GetVisualName(),time.toString()));
	return id;
}

unsigned int Cron::RegisterOneShot(PluginInterface * p, unsigned int interval, Bunny * b, unsigned int type, QVariant data, const char * callback)
{
	if(!p)
	{
		LogError("Cron : pointer is null !");
		return 0;
	}

	Cron & theCron = Instance();
	unsigned id = ++theCron.lastGivenID;
	if(!id)
		LogError("Warning Cron::Register : lastGivenID overlapped !");

	CronElement e;
	e.interval = 0;
	e.callback = callback;
	e.plugin = p;
	e.bunny = b;
	e.data = data;
	e.id = id;
	e.type = type;

	// Compute next run
	QDateTime time = QDateTime::currentDateTime();

	e.next_run = time.toTime_t() + (interval*60);
	time = time.addSecs(interval*60);
	theCron.AddCron(e);

	//LogInfo(QString("Cron Register : %1 - %2").arg(p->GetVisualName(),time.toString()));
	return id;
}

unsigned int Cron::RegisterDaily(PluginInterface * p, QTime const& time, Bunny * b, unsigned int type, QVariant data, const char * callback)
{
	if(!p)
	{
		LogError("Cron : pointer is null !");
		return 0;
	}
	if(!time.isValid())
	{
		LogError(QString("Cron : invalid time (bunny %1, plugin %2)").arg(QString(b->GetID()), p->GetName()));
		return 0;
	}

	Cron & theCron = Instance();
	unsigned id = ++theCron.lastGivenID;
	if(!id)
		LogError("Warning Cron::Register : lastGivenID overlapped !");

	CronElement e;
	e.interval = 24 * 60 * 60; // DAILY
	e.callback = callback;
	e.plugin = p;
	e.bunny = b;
	e.data = data;
	e.id = id;
	e.type = type;

	// Compute next run
	QDateTime now = QDateTime::currentDateTime();
	QDateTime nextTime = now;
	if(b != NULL)
	{
		nextTime.setTime(Translator::MakeServerTime(b->GetGlobalSetting("TimeZone","UTC").toString(), time));
		while(nextTime < now)
			nextTime = nextTime.addDays(1); // Tomorrow
	}
	else
	{
		nextTime.setTime(time);
		if(nextTime < now)
			nextTime = nextTime.addDays(1); // Tomorrow
	}

	e.next_run = nextTime.toTime_t();
	theCron.AddCron(e);

	//LogInfo(QString("Cron Register : %1 - %2").arg(p->GetVisualName(),time.toString()));
	return id;
}

unsigned int Cron::RegisterWeekly(PluginInterface * p, Qt::DayOfWeek day, QTime const& time, Bunny * b, unsigned int type, QVariant data, const char * callback)
{
	if(!p)
	{
		LogError("Cron : pointer is null !");
		return 0;
	}

	Cron & theCron = Instance();
	unsigned id = ++theCron.lastGivenID;
	if(!id)
		LogError("Warning Cron::Register : lastGivenID overlapped !");

	CronElement e;
	e.interval = 7 * 24 * 60 * 60; // Weekly
	e.callback = callback;
	e.plugin = p;
	e.bunny = b;
	e.data = data;
	e.id = id;
	e.type = type;

	// Compute next run
	QDateTime now = QDateTime::currentDateTime();
	QDateTime nextTime = now;
	if(b != NULL)
	{
		nextTime.setTime(Translator::MakeServerTime(b->GetGlobalSetting("TimeZone","UTC").toString(), time));
    int dayDiff = Translator::MakeServerDayDiff(b->GetGlobalSetting("TimeZone","UTC").toString(), time);
    //LogInfo(QString("dayDiff: %1, addDays %2, now %3, next %4/%5").arg(dayDiff).arg(day + dayDiff - now.date().dayOfWeek()).arg(now.toString()).arg(nextTime.toString()).arg(nextTime.addDays(day + dayDiff - now.date().dayOfWeek()).toString()));
		nextTime = nextTime.addDays(day + dayDiff - now.date().dayOfWeek());
		if(nextTime < now)
			nextTime = nextTime.addDays(7); // Next week
	}
	else
	{
		nextTime.setTime(time);
		nextTime = nextTime.addDays(day - now.date().dayOfWeek());
		if(nextTime < now)
			nextTime = nextTime.addDays(7); // Next week
	}

	e.next_run = nextTime.toTime_t();
	theCron.AddCron(e);

	//LogInfo(QString("Cron Register : %1 - %2").arg(p->GetVisualName(),nextTime.toString()));
	return id;
}

unsigned int Cron::RegisterMonthly(PluginInterface * p, int day, QTime const& time, Bunny * b, unsigned int type, QVariant data, const char * callback)
{
	if(!p)
	{
		LogError("Cron : pointer is null !");
		return 0;
	}

	Cron & theCron = Instance();
	unsigned id = ++theCron.lastGivenID;
	if(!id)
		LogError("Warning Cron::Register : lastGivenID overlapped !");

	CronElement e;
	e.interval = 0; // Monthly
	e.day = day;
	e.callback = callback;
	e.plugin = p;
	e.bunny = b;
	e.data = data;
	e.id = id;
	e.type = type;

	return id;

	// Compute next run
	QDateTime now = QDateTime::currentDateTime();
	QDateTime nextTime = now;
	if(b != NULL)
	{
		nextTime.setTime(Translator::MakeServerTime(b->GetGlobalSetting("TimeZone","UTC").toString(), time));
		nextTime = nextTime.addDays(( day + Translator::MakeServerDayDiff(b->GetGlobalSetting("TimeZone","UTC").toString(), time) ) % 7 - now.date().dayOfWeek());
		if(nextTime < now)
			nextTime = nextTime.addDays(7); // Next week
	}
	else
	{
		nextTime.setTime(time);
		nextTime = nextTime.addDays(day - now.date().dayOfWeek());
		if(nextTime < now)
			nextTime = nextTime.addDays(7); // Next week
	}

	e.next_run = nextTime.toTime_t();
	theCron.AddCron(e);

	//LogInfo(QString("Cron Register : %1 - %2").arg(p->GetVisualName(),nextTime.toString()));
	return id;
}

QDateTime Cron::ComputeNextMonthly(int day, QTime const& time, Bunny * b)
{
	QDateTime now = QDateTime::currentDateTime();
	QDateTime nextTime = QDateTime();
	if(b != NULL)
	{
		nextTime.setTime(Translator::MakeServerTime(b->GetGlobalSetting("TimeZone","UTC").toString(), time));
		int dayDiff = Translator::MakeServerDayDiff(b->GetGlobalSetting("TimeZone","UTC").toString(), time);

		nextTime = nextTime.addDays(( day + dayDiff ) % 7 - now.date().dayOfWeek());
		if(nextTime < now)
			nextTime = nextTime.addDays(7); // Next week
	}
	else
	{
		nextTime.setTime(time);
		nextTime = nextTime.addDays(day - now.date().dayOfWeek());
		if(nextTime < now)
			nextTime = nextTime.addDays(7); // Next week
	}
	return nextTime;
}

void Cron::Unregister(PluginInterface * p, unsigned int id)
{
	Cron & theCron = Instance();
	auto e = theCron.CronElements.begin();
	while(e != theCron.CronElements.end())
	{
		if(e->plugin == p && e->id == id)
		{
			/*LogInfo(QString("Cron Unregister : %1/%2 - next: %3").arg(p->GetName())
																													.arg(id)
																													.arg(QDateTime::fromTime_t(e->next_run).toString())
																												 );
			*/
			e = theCron.CronElements.erase(e);
		} else
			e++;
	}
}

void Cron::UnregisterAllForBunny(PluginInterface * p, Bunny * b)
{
	Cron & theCron = Instance();
	auto e = theCron.CronElements.begin();
	while(e != theCron.CronElements.end())
	{
		if(e->plugin == p && e->bunny == b)
		{
			/*LogInfo(QString("Cron Unregister : %1/%2 - next: %3").arg(p->GetName())
																											 .arg(b->GetBunnyName())
																											 .arg(QDateTime::fromTime_t(e->next_run).toString())
																											);
			*/
			e = theCron.CronElements.erase(e);
		} else
			e++;
	}
}

void Cron::UnregisterAll(PluginInterface * p)
{
	Cron & theCron = Instance();
auto e = theCron.CronElements.begin();
	while(e != theCron.CronElements.end())
	{
		if(e->plugin == p)
		{
			/*LogInfo(QString("Cron Unregister : %1 - next: %2").arg(p->GetName())
																											 .arg(QDateTime::fromTime_t(e->next_run).toString())
																											);
			*/
			e = theCron.CronElements.erase(e);
		} else
			e++;
	}
}

Cron& Cron::Instance() {
  static Cron theCron;
  return theCron;
}

void Cron::InitApiCalls()
{
	DECLARE_API_CALL("cron()", &Cron::Api_cron);
}

API_CALL(Cron::Api_cron)
{
	if(!account.IsAdmin())
		return new ApiAnswers::Error(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QString crons = "<crons>";
		std::list<CronElement>::iterator i;
		std::list<CronElement> list = Cron::ListAllCron();
		for (i = list.begin(); i != list.end(); ++i)
		{
			if(
				( !hRequest.HasArg("plugin") || hRequest.GetArg("plugin") == (*i).plugin->GetName() )
				&& ( !hRequest.HasArg("bunny") || hRequest.GetArg("bunny") == QString((*i).bunny->GetID()) )
			)
			{
				crons += "<cron>";
				crons += "<type>" + QString::number((*i).type) + "</type>";
				crons += "<plugin>" + ((*i).plugin != NULL ? (*i).plugin->GetName() : "") + "</plugin>";
				crons += "<bunny>" + ((*i).bunny != NULL ? (*i).bunny->GetID() : "") + "</bunny>";
				crons += "<next_run>" + QString::number((*i).next_run) + "</next_run>";
				crons += "<callback>" + QString((*i).callback) + "</callback>";
				crons += "<interval>" + QString::number((*i).interval) + "</interval>";
				crons += "<day>" + QString::number((*i).day) + "</day>";
				crons += "<month>" + QString::number((*i).month) + "</month>";
				crons += "<data_int>" + QString::number((*i).data.toInt()) + "</data_int>";
				crons += "<data_string>" + (*i).data.toString() + "</data_string>";
				crons += "</cron>";
			}
		}
		crons += "</crons>";
		return new ApiAnswers::Xml(crons);
	}
	else if(action == "debug")
	{
		if(!hRequest.HasArg("subaction"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("subaction"));

		QString subaction = hRequest.GetArg("subaction");

		if(!hRequest.HasArg("bunny"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("bunny"));

		QByteArray bunnyID = hRequest.GetArg("bunny").toLatin1();
		Bunny * b = BunnyManager::GetBunny(bunnyID);

		if(subaction == "set")
		{
			if(!hRequest.HasArg("value"))
				return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("value"));

			QString value = hRequest.GetArg("value");
			b->SetGlobalSetting("CronDebug", value == "true" ? true : false);
        		return new ApiAnswers::Ok(Translator::tr("Value '%1' set to '%2' for bunny %3", account).arg("CronDebug", value == "true" ? "true" : "false", QString(b->GetID())));
		}
		else if(subaction == "get")
		{
        		return new ApiAnswers::Ok(b->GetGlobalSetting("CronDebug", false).toBool() ? "true" : "false");
		}
		else
		{
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("subaction"));
		}
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}
