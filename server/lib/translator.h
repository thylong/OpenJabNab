#ifndef _TRANSLATOR_H_
#define _TRANSLATOR_H_

#include <QHash>
#include <QDateTime>
#include <QMap>
#include <QTranslator>
#include <QNetworkAccessManager>

#include "apihandler.h"
#include "apimanager.h"
#include "global.h"
#include "bunny.h"
#include "account.h"

class QPluginLoader;
class OJN_EXPORT Translator : public ApiHandler<Translator>
{
	friend class ApiManager;
public:
	// General
        static Translator & Instance();
	static void Init();
	static void Close();

	// Google translate
	static QString googleTranslate(QString, QString);
	static QString detectLanguage(QString);

	// Timezone
	static QDateTime GetCurrentTime(QString);
	static QDateTime GetTimezoneTime(QString, QDateTime);
	static QDateTime MakeServerTime(QString, QDateTime);
	static QTime MakeServerTime(QString, QTime);
	static int MakeServerDayDiff(QString, QTime);
	static QDateTime decodeDstDate(QString);
	static bool isFixedDstDate(QString);

	// Time
	static QString getDay(int);
	static QString getMonth(int);
	static QDate extractDate(QString);

	static QString makeDate(QString, bool);
	static QString makeDate(QDateTime, bool);
	static QString makeDate(QDate, bool);
	static QString makeDate(QString);
	static QString makeDate(QDateTime);
	static QString makeDate(QDate);

	static QString makeDate(QString, QString, bool);
	static QString makeDate(QDateTime, QString, bool);
	static QString makeDate(QDate, QString, bool);
	static QString makeDate(QString, QString);
	static QString makeDate(QDateTime, QString);
	static QString makeDate(QDate, QString);

	static QString makeDate(QString, Bunny *, bool);
	static QString makeDate(QDateTime, Bunny *, bool);
	static QString makeDate(QDate, Bunny *, bool);
	static QString makeDate(QString, Bunny *);
	static QString makeDate(QDateTime, Bunny *);
	static QString makeDate(QDate, Bunny *);

	static QString makeDate(QString, Account const&, bool);
	static QString makeDate(QDateTime, Account const&, bool);
	static QString makeDate(QDate, Account const&, bool);
	static QString makeDate(QString, Account const&);
	static QString makeDate(QDateTime, Account const&);
	static QString makeDate(QDate, Account const&);

	// Language
	static QString tr(QString);
	static QString tr(QString, QString);
	static QString tr(QString, Account const&);
	static QString tr(QString, Bunny *);
	static QString getLanguage();
	static QString getLanguage(Account const&);
	static QString getLanguage(Bunny *);
	static QTranslator * getTranslator(QString);
	static QMap<QString, QVariant> GetLanguageList(QStringList, QString);
	static QMap<QString, QVariant> GetLanguageList(QStringList);
	static QString GetLanguage(QString);
	// API
	static void InitApiCalls();

protected:
	// API
	API_CALL(Api_GetListOfTimezones);
	API_CALL(Api_getTime);
	API_CALL(Api_Translation);

	virtual ~Translator();
private:
	Translator();
	void loadTranslations();

	static int getLastDayOfMonth(int, int);
	static int getLast(int, int, int);
	static int getFirst(int, int, int);
	static int getSecond(int, int, int);
	QMap< QString, QTranslator * > translators;
	QStringList days;
	QStringList months;
  QNetworkAccessManager _http;
};

inline void Translator::Init()
{
	InitApiCalls();
	Instance().loadTranslations();
}

inline void Translator::loadTranslations()
{
	QStringList languages;
	languages << "fr" << "it" << "de" << "es";
	translators.clear();

	foreach(QString lng, languages)
	{
		LogInfo(QString("Loading %1 language").arg(lng));
		QTranslator *t = new QTranslator();
		if(t->load("openjabnab_" + lng + ".qm", GlobalSettings::GetConfigDir().append("translations/")))
    {
      translators.insert(lng, t);
      LogInfo(Translator::tr("Language %1 loaded", lng).arg(lng));
    }
    else
    {
      LogInfo(Translator::tr("Couldn't load Language %1", lng).arg(lng));
    }
	}

	days.append("");
	days.append(Translator::tr("monday"));
	days.append(Translator::tr("tuesday"));
	days.append(Translator::tr("wednesday"));
	days.append(Translator::tr("thursday"));
	days.append(Translator::tr("friday"));
	days.append(Translator::tr("saturday"));
	days.append(Translator::tr("sunday"));

	months.append("");
	months.append(Translator::tr("january"));
	months.append(Translator::tr("february"));
	months.append(Translator::tr("march"));
	months.append(Translator::tr("april"));
	months.append(Translator::tr("may"));
	months.append(Translator::tr("june"));
	months.append(Translator::tr("july"));
	months.append(Translator::tr("august"));
	months.append(Translator::tr("september"));
	months.append(Translator::tr("october"));
	months.append(Translator::tr("november"));
	months.append(Translator::tr("december"));
}

inline void Translator::Close()
{
}

#endif
