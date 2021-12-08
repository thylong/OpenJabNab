#ifndef _CRON_H_
#define _CRON_H_

#include <list>
#include <QObject>
#include <QVariant>
#include "global.h"
#include "apihandler.h"

#include <functional>

class QTimer;

class PluginInterface;
class Bunny;

class OJN_EXPORT Cron
	: public QObject
	, public ApiHandler<Cron>
{
	Q_OBJECT

public:
	static Cron& Instance();

	enum CronType { Classic = 0, Random};
	typedef std::function<void(Bunny*, QVariant, CronType)> Callback_t;

	typedef struct
	{
		PluginInterface * plugin;
		Bunny * bunny;
		QVariant data;
		unsigned int type;
		Callback_t   callback;
		unsigned int id;
		unsigned int interval; // in min
		unsigned int next_run; // in seconds since 1970-01-01T00:00:00
		unsigned int day; // != 0 is a monthly or yearly cron
		unsigned int month; // != 0 is a yearly cron
	} CronElement;

	// Will fire at hh:mm and every interval minutes
	static unsigned int Register(PluginInterface *, unsigned int interval, unsigned int offsetH, unsigned int offsetM, Bunny * b, unsigned int type = 0, QVariant data = QVariant(), Callback_t callback = nullptr);
	// Will fire in interval minutes
	static unsigned int RegisterOneShot(PluginInterface *, unsigned int interval, Bunny * b, unsigned int type = 0, QVariant data = QVariant(), Callback_t callback = nullptr);
	// Will fire each day at time
	static unsigned int RegisterDaily(PluginInterface * p, QTime const& time, Bunny * b, unsigned int type = 0, QVariant data = QVariant(), Callback_t callback = nullptr);
	// Will fire each week at day:time
	static unsigned int RegisterWeekly(PluginInterface * p, Qt::DayOfWeek day, QTime const& time, Bunny * b, unsigned int type = 0, QVariant data = QVariant(), Callback_t callback = nullptr);
	// Will fire each month at day:time
	static unsigned int RegisterMonthly(PluginInterface * p, int day, QTime const& time, Bunny * b, unsigned int type = 0, QVariant data = QVariant(), Callback_t callback = nullptr);
	// Will fire each year at month/day:time
	static unsigned int RegisterYearly(PluginInterface * p, int month, int day, QTime const& time, Bunny * b, unsigned int type = 0, QVariant data = QVariant(), Callback_t callback = nullptr);
	static void Unregister(PluginInterface *, unsigned int id);
	static void UnregisterAllForBunny(PluginInterface *, Bunny *);
	static void UnregisterAll(PluginInterface *);
	static QTime mkTime(QString);

	static QMap<PluginInterface *, QDateTime> ListBunnyCron(Bunny *);
	static std::list<CronElement> ListAllBunnyCron(Bunny *);
	static std::list<CronElement> ListAllCron();

	static inline void Init() { InitApiCalls(); };
	//static void Close();
	static void InitApiCalls();
	static void LogDebugCron(CronElement const&);
	void OnTimer();

private:
	Cron();
	virtual ~Cron();

	void AddCron(CronElement const&);

	QTimer* _tmr;

	unsigned int lastGivenID;
	std::list<CronElement> CronElements;

	QDateTime ComputeNextMonthly(int day, QTime const& time, Bunny * b);
	QDateTime ComputeNextYearly(int month, int day, QTime const& time, Bunny * b);

	API_CALL(Api_cron);
};

#endif
