#include <QCryptographicHash>
#include <QDataStream>
#include <QEventLoop>
#include <QFile>
#include <QNetworkAccessManager>
#include <QUrl>
#include <QNetworkRequest>
#include <QNetworkReply>
#include <QObject>
#include <QPluginLoader>
#include <QStringList>
#include <QUrl>
#include <QRegExp>
#include <QEventLoop>
#include "log.h"
#include "settings.h"
#include "translator.h"
#include <cstdlib>
#include <QTimeZone>

Translator::Translator()
{
}

Translator & Translator::Instance()
{
	static Translator p;
	return p;
}

Translator::~Translator()
{
	foreach(QTranslator * t, translators)
		delete t;
}

QString Translator::detectLanguage(QString string)
{
/*
GET /translate_a/t?client=t&sl=auto&tl=en&hl=fr&sc=2&ie=UTF-8&oe=UTF-8&uptl=en&alttl=fr&oc=1&otf=2&ssel=3&tsel=6&q=Ciao%20bella HTTP/1.1
Host: translate.google.fr
User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:28.0) Gecko/20100101 Firefox/28.0
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*          /      *;q=0.8
Accept-Language: fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3
Accept-Encoding: gzip, deflate
Cookie: PREF=ID=3aa6daef7beccc26:TM=1398261220:LM=1398261220:S=O0t9Tm4MCmbGECD4
Connection: keep-alive
*/
	string = "en";
	return "en";
}

QString Translator::googleTranslate(QString text, QString language)
{
	// http://translate.google.fr/translate_a/t?client=t&sl=en&tl=fr&hl=fr&sc=2&ie=UTF-8&oe=UTF-8&oc=1&prev=conf&psl=en&ptl=de&otf=1&it=sel.1591&ssel=0&tsel=3&q=Germany
	QEventLoop loop;

	QNetworkRequest req(QUrl("translate.google.fr/translate_a/t?client=t&sl=en&tl="+language+"&hl="+language+"&sc=2&ie=UTF-8&oe=UTF-8&oc=1&prev=conf&psl=en&ptl=de&otf=1&it=sel.1591&ssel=0&tsel=3&q=" + QUrl::toPercentEncoding(text)));

	req.setRawHeader("Host", "translate.google.fr");
	req.setRawHeader("Referer", "http://translate.google.fr/");
	req.setRawHeader("User-Agent", GlobalSettings::GetString("Config/UserAgent").toLatin1());
	QNetworkAccessManager http;
	auto* rep = http.get(req);
  QObject::connect(rep, &QNetworkReply::finished, &loop, &QEventLoop::quit);
  QObject::connect(rep, qOverload<QNetworkReply::NetworkError>(&QNetworkReply::error), &loop, &QEventLoop::quit);
	loop.exec();

	QString content = rep->readAll();
	delete rep;
	QString translation = text;
	QRegExp rx("^\\[\\[\\[\"(.*)\",\"(.*)\",\"\",\"\"\\]\\]");
	if(rx.indexIn(content) != -1)
		translation = rx.cap(1).trimmed();
	return translation;
}

QDate Translator::extractDate(QString date)
{
	QRegExp rx("(\\d\\d?/\\d\\d?/\\d\\d\\d\\d)");
	if(rx.indexIn(date) != -1)
	{
					date = rx.cap(1).trimmed();
					return QDate::fromString(date, "d/M/yyyy");
	}
	else if(rx.setPattern("(\\d\\d\\d\\d-\\d\\d?-\\d\\d?)"), rx.indexIn(date) != -1)
	{
					date = rx.cap(1).trimmed();
					return QDate::fromString(date, "yyyy-M-d");
	}
	return QDate();
}

QString Translator::makeDate(QString date, QString lng, bool year)
{
	QDate d = Translator::extractDate(date);
	if(!d.isNull())
		return Translator::makeDate(d, lng, year);
	return date;
}

QString Translator::makeDate(QString date, QString lng)
{
	return Translator::makeDate(date, lng, true);
}

QString Translator::makeDate(QDateTime date, QString lng)
{
	return Translator::makeDate(date.date(), lng, true);
}

QString Translator::makeDate(QDate date, QString lng, bool year)
{
	int dow = date.dayOfWeek();
	int d = date.day();
	int m = date.month();
	int y = date.year();
	if(year)
		return Translator::tr("%1, %2 %3, %4", lng).arg(Translator::tr(Instance().days.at(dow), lng), Translator::tr(Instance().months.at(m), lng), QString::number(d), QString::number(y));
	return Translator::tr("%1, %2 %3", lng).arg(Translator::tr(Instance().days.at(dow), lng), Translator::tr(Instance().months.at(m), lng), QString::number(d));
}

QString Translator::makeDate(QDateTime date, QString lng, bool year)
{
	return Translator::makeDate(date.date(), lng, year);
}

QString Translator::makeDate(QDate date, QString lng)
{
	return Translator::makeDate(date, lng, true);
}

QString Translator::makeDate(QString date, Account const& a, bool year)
{
	return Translator::makeDate(date, getLanguage(a), year);
}

QString Translator::makeDate(QString date, Account const& a)
{
	return Translator::makeDate(date, getLanguage(a), true);
}

QString Translator::makeDate(QDateTime date, Account const& a)
{
	return Translator::makeDate(date.date(), getLanguage(a), true);
}

QString Translator::makeDate(QDate date, Account const& a, bool year)
{
	return Translator::makeDate(date, getLanguage(a), year);
}

QString Translator::makeDate(QDateTime date, Account const& a, bool year)
{
	return Translator::makeDate(date.date(), getLanguage(a), year);
}

QString Translator::makeDate(QDate date, Account const& a)
{
	return Translator::makeDate(date, getLanguage(a), true);
}

QString Translator::makeDate(QString date, Bunny * b, bool year)
{
	return Translator::makeDate(date, getLanguage(b), year);
}

QString Translator::makeDate(QString date, Bunny * b)
{
	return Translator::makeDate(date, getLanguage(b), true);
}

QString Translator::makeDate(QDateTime date, Bunny * b)
{
	return Translator::makeDate(date.date(), getLanguage(b), true);
}

QString Translator::makeDate(QDate date, Bunny * b, bool year)
{
	return Translator::makeDate(date, getLanguage(b), year);
}

QString Translator::makeDate(QDateTime date, Bunny * b, bool year)
{
	return Translator::makeDate(date.date(), getLanguage(b), year);
}

QString Translator::makeDate(QDate date, Bunny * b)
{
	return Translator::makeDate(date, getLanguage(b), true);
}

QString Translator::makeDate(QString date, bool year)
{
	return Translator::makeDate(QDate::fromString("yyyy-MM-dd", date), "en", year);
}

QString Translator::makeDate(QString date)
{
	return Translator::makeDate(date, "en", true);
}

QString Translator::makeDate(QDateTime date)
{
	return Translator::makeDate(date.date(), "en", true);
}

QString Translator::makeDate(QDate date, bool year)
{
	return Translator::makeDate(date, "en", year);
}

QString Translator::makeDate(QDateTime date, bool year)
{
	return Translator::makeDate(date.date(), "en", year);
}

QString Translator::makeDate(QDate date)
{
	return Translator::makeDate(date, "en", true);
}

QTranslator * Translator::getTranslator(QString lng)
{
	if(Instance().translators.contains(lng))
		return Instance().translators.value(lng);
	return NULL;
}

QString Translator::tr(QString s, Bunny * b )
{
	return Translator::tr(s, getLanguage(b));
}

QString Translator::tr(QString s, Account const& a )
{
	return Translator::tr(s, getLanguage(a));
}

QString Translator::tr(QString s )
{
	return Translator::tr(s, "en");
}

QString Translator::tr(QString s, QString lng)
{
	QTranslator * t = Translator::getTranslator(lng);
	if(t == NULL)
		return s;
	QString tr = t->translate("Translator", s.toLatin1());
	if(tr.length())
		return tr;
	return s;
}

QString Translator::GetLanguage(QString lng)
{
	QMap<QString, QString> languages;
	languages.insert("fr", "French");
	languages.insert("en", "English");
	languages.insert("it", "Italian");
	languages.insert("de", "Deutch");
	languages.insert("es", "Spanish");
	if(languages.contains(lng))
	{
		return languages.value(lng);
	}
	return lng;
}

QMap<QString, QVariant> Translator::GetLanguageList(QStringList list, QString lng)
{
	QMap<QString, QVariant> languages;
	foreach(QString language, list)
	{
		languages.insert(language, Translator::tr(GetLanguage(language), lng));
	}
	return languages;
}
QMap<QString, QVariant> Translator::GetLanguageList(QStringList list)
{
	QMap<QString, QVariant> languages;
	foreach(QString lng, list)
	{
		languages.insert(lng, GetLanguage(lng));
	}
	return languages;
}

QString Translator::getLanguage()
{
	return GlobalSettings::Get("Language/Server", "en").toString();
}

QString Translator::getLanguage(Bunny * b)
{
	return b->GetLanguage();
}

QString Translator::getLanguage(Account const& a)
{
	return a.GetLanguage();
}

bool Translator::isFixedDstDate(QString str)
{
        QRegExp rx("^in (\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		return true;
        }
        rx.setPattern("^(\\d*)-(\\d*)-(\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		return true;
        }
	return false;
}

QDateTime Translator::decodeDstDate(QString str)
{
        QRegExp rx("^last (\\d*) of (\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		int lastDay = rx.cap(1).toInt();
		int ofMonth = rx.cap(2).toInt();
		int last = Translator::getLast(lastDay, ofMonth, QDate::currentDate().year());

		return QDateTime(QDate(QDate::currentDate().year(), ofMonth, last), QTime(rx.cap(3).toInt(), rx.cap(4).toInt()));
        }
        rx.setPattern("^first (\\d*) of (\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		int firstDay = rx.cap(1).toInt();
		int ofMonth = rx.cap(2).toInt();
		int first = Translator::getFirst(firstDay, ofMonth, QDate::currentDate().year());

		return QDateTime(QDate(QDate::currentDate().year(), ofMonth, first), QTime(rx.cap(3).toInt(), rx.cap(4).toInt()));
        }
        rx.setPattern("^second (\\d*) of (\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		int secondDay = rx.cap(1).toInt();
		int ofMonth = rx.cap(2).toInt();
		int second = Translator::getSecond(secondDay, ofMonth, QDate::currentDate().year());

		return QDateTime(QDate(QDate::currentDate().year(), ofMonth, second), QTime(rx.cap(3).toInt(), rx.cap(4).toInt()));
        }
        rx.setPattern("^in (\\d*), last (\\d*) of (\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		int lastDay = rx.cap(2).toInt();
		int ofMonth = rx.cap(3).toInt();
		int last = Translator::getLast(lastDay, ofMonth, QDate::currentDate().year());

		return QDateTime(QDate(rx.cap(1).toInt(), ofMonth, last), QTime(rx.cap(4).toInt(), rx.cap(5).toInt()));
        }
        rx.setPattern("^in (\\d*), first (\\d*) of (\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		int firstDay = rx.cap(2).toInt();
		int ofMonth = rx.cap(3).toInt();
		int first = Translator::getFirst(firstDay, ofMonth, QDate::currentDate().year());

		return QDateTime(QDate(rx.cap(1).toInt(), ofMonth, first), QTime(rx.cap(4).toInt(), rx.cap(5).toInt()));
        }
        rx.setPattern("^in (\\d*), second (\\d*) of (\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		int secondDay = rx.cap(2).toInt();
		int ofMonth = rx.cap(3).toInt();
		int second = Translator::getSecond(secondDay, ofMonth, QDate::currentDate().year());

		return QDateTime(QDate(rx.cap(1).toInt(), ofMonth, second), QTime(rx.cap(4).toInt(), rx.cap(5).toInt()));
        }
        rx.setPattern("^(\\d*)-(\\d*)-(\\d*) at (\\d*):(\\d*)$");
        if(rx.indexIn(str) != -1)
        {
		return QDateTime(QDate(rx.cap(1).toInt(), rx.cap(2).toInt(), rx.cap(3).toInt()), QTime(rx.cap(4).toInt(), rx.cap(5).toInt()));
        }

	LogError("Can't decode timezone DST data : " + str);
	return QDateTime::currentDateTime ();
}

int Translator::getLastDayOfMonth(int m, int y)
{
	int d = 31;
	while(!QDate::isValid(y, m, d))
	{
		d--;
	}
	return d;
}

int Translator::getLast(int d, int m, int y)
{
	int l = Translator::getLastDayOfMonth(m, y);
	int dd = QDate(y, m, l).dayOfWeek();
	if(dd >= d) return l - (dd - d);
	return l - 7 + (d - dd);
}

int Translator::getSecond(int d, int m, int y)
{
	int dd = QDate(y, m, 1).dayOfWeek();
	return (d - dd) + (dd > d ? 15 : 8);
}

int Translator::getFirst(int d, int m, int y)
{
	int dd = QDate(y, m, 1).dayOfWeek();
	return (d - dd) + (dd > d ? 8 : 1);
}

QDateTime Translator::MakeServerTime(QString tz, QDateTime date)
{
	QTimeZone fromTz(tz.toLatin1()),
            srvTz(GlobalSettings::GetString("Config/TimeZone","UTC").toLatin1());
  date.setTimeZone(fromTz);
 // LogInfo(QString("Convert date %1 from %2 to %3: %4").arg(date.toString("yyyy-MM-dd hh:mm:ss")).arg(tz).arg(GlobalSettings::GetString("Config/TimeZone","UTC")).arg(date.toTimeZone(srvTz).toString("yyyy-MM-dd hh:mm:ss")));
  return date.toTimeZone(srvTz);
}

QTime Translator::MakeServerTime(QString tz, QTime time)
{
  QDateTime date = QDateTime::currentDateTime(); date.setTime(time);
  return MakeServerTime(tz,date).time();
}

int Translator::MakeServerDayDiff(QString tz, QTime time)
{
  QDateTime date = QDateTime::currentDateTime(); date.setTime(time);
  return date.daysTo(MakeServerTime(tz,date));
}

QDateTime Translator::GetCurrentTime(QString tz)
{
	return QDateTime::currentDateTime().toTimeZone(QTimeZone(tz.toLatin1()));
}

QDateTime Translator::GetTimezoneTime(QString tz, QDateTime date)
{
	return date.toTimeZone(QTimeZone(tz.toLatin1()));
}

void Translator::InitApiCalls()
{
	DECLARE_API_CALL("gettime()", &Translator::Api_getTime);
	DECLARE_API_CALL("listTimezones()", &Translator::Api_GetListOfTimezones);
	DECLARE_API_CALL("translation()", &Translator::Api_Translation);
}

API_CALL(Translator::Api_getTime)
{
	Q_UNUSED(account);

	QMap<QString, QVariant> list;

	QDateTime time = QDateTime::currentDateTime();
	if(hRequest.HasArg("time"))
	{
		time = QDateTime::fromString(hRequest.GetArg("time"), "yyyy-MM-dd hh:mm:ss");
	}
	list.insert("UTC", GetTimezoneTime("UTC", time).toString( "yyyy-MM-dd hh:mm:ss" ));
	list.insert("server", time.toString( "yyyy-MM-dd hh:mm:ss" ));
	if(hRequest.HasArg("tz"))
	{
		list.insert(hRequest.GetArg("tz"), GetTimezoneTime(hRequest.GetArg("tz"), time).toString( "yyyy-MM-dd hh:mm:ss" ));
	}
	return new ApiAnswers::MappedList(list);
}

API_CALL(Translator::Api_GetListOfTimezones)
{
	Q_UNUSED(hRequest);
	Q_UNUSED(account);
  QMap<QString, QVariant> outList;

  const auto& tzList = QTimeZone::availableTimeZoneIds();
  foreach(const QByteArray& tz, tzList)
  {
    outList.insert(QString(tz), GetCurrentTime(QString(tz)).toString( "yyyy-MM-dd hh:mm:ss" ));
  }

	return new ApiAnswers::MappedList(outList);
}

API_CALL(Translator::Api_Translation)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "reload")
	{
		loadTranslations();
		return new ApiAnswers::Ok(Translator::tr("Translations reloaded", account));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

