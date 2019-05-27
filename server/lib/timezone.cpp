#include <QCoreApplication>
#include <QCryptographicHash>
#include <QUuid>
#include <QDateTime>
#include <QDir>
#include <QFile>
#include <QRegExp>
#include "timezone.h"
#include "log.h"
#include "account.h"
#include "translator.h"
#include <QRegExp>

Timezone::Timezone(QString const& zone, QString const& a, const QDir& tzDir)
{
	Initialize(zone,tzDir);
	if(aliases.contains(a))
	{
		alias = a;
	}
}

Timezone::Timezone(QString const& zone, const QDir& tzDir)
{
	Initialize(zone,tzDir);
}

void Timezone::Initialize(QString const& zone, const QDir& timezonesDir)
{
	QStringList z = zone.split(";");
	if(z.count() >= 1)
	{
		z = z.at(0).split("/");
		area = z[0];
		if(z.count() > 1)
			location = z[1];
		stdCode = "";
		stdName = "";
		stdOffset = 0;
		dstCode = "";
		dstName = "";
		dstOffset = 0;

		alias = area + "/" + location;

		configFileName = timezonesDir.absoluteFilePath(area.toLower() + "_" + location.toLower()+".dat");

		// Check if config file exists and load it
		if (QFile::exists(configFileName))
			LoadConfig();
	}
/*
	QDateTime current = QDateTime::currentDateTime ();
	LogDebug(area + "/" + location + " : " + QString::number(getOffsetAt(current)) + " on " + current.toString("ddd MMMM d yy hh:mm:ss"));
	current = current.addMonths(-12);
	for(int i=0; i<36; i++)
	{
		current = current.addMonths(1);
		LogDebug(area + "/" + location + " : " + QString::number(getOffsetAt(current)) + " on " + current.toString("ddd MMMM d yy hh:mm:ss"));
	}
*/
}

Timezone::~Timezone()
{
}

void Timezone::LoadConfig()
{
	QFile file(configFileName);
	if (!file.open(QIODevice::ReadOnly))
	{
		LogError(QString("Cannot open config file for reading : %1").arg(configFileName));
		return;
	}

	QDataStream in(&file);
	in.setVersion(QDataStream::Qt_4_3);
	in >> area >> location >> stdCode >> stdName >> stdOffset >> dstCode >> dstName >> dstOffset >> dstList;
	if (in.status() != QDataStream::Ok)
	{
		LogWarning(QString("Problem when loading config file for timezone : %1").arg(QString(area + "/" + location)));
	}
	else
	{
		in >> aliases;
		if (in.status() == QDataStream::ReadPastEnd)
		{
			aliases.clear();
		}
	}
	if(aliases.count() == 0)
	{
		aliases.append(area + "/" + location);
	}
}

QStringList Timezone::explodeDst(QString dst)
{
	return dst.split("|");
}

float Timezone::getOffsetAt(QDateTime datetime)
{
	if(dstList.length() == 0)
		return stdOffset;
	foreach(QString dst, dstList)
	{
		QStringList list = explodeDst(dst);
		QDateTime start = Translator::decodeDstDate(list.at(0));
		QDateTime end = Translator::decodeDstDate(list.at(1));
		bool fixedStart = Translator::isFixedDstDate(list.at(0));
		bool fixedEnd = Translator::isFixedDstDate(list.at(1));
		if(fixedStart != fixedEnd)
		{
			LogWarning("Bad DST configuration, fixed not the same");
		}
		else
		{
			bool fixed = fixedStart && fixedEnd;
			if(start > end)
			{
				if(!fixed)
					end = end.addYears(1);
				else
					LogWarning("Bad DST configuration, start is after end");
			}
			if(!fixed)
			{
				while(start > datetime)
				{
					start = start.addYears(-1);
					end = end.addYears(-1);
				}
			}
			if(datetime.secsTo(start) < 0 && datetime.secsTo(end) > 0)
				return dstOffset;
			if(!fixed)
			{
				while(end < datetime)
				{
					start = start.addYears(1);
					end = end.addYears(1);
				}
			}
			if(datetime.secsTo(start) < 0 && datetime.secsTo(end) > 0)
				return dstOffset;
		}
	}
	return stdOffset;
}

float Timezone::getCurrentOffset()
{
	QDateTime current = QDateTime::currentDateTime();
	return getOffsetAt(current);
}

bool Timezone::dstActivatedAt(QDateTime datetime)
{
	if(getOffsetAt(datetime) == stdOffset)
		return true;
	return false;
}

bool Timezone::dstActivated()
{
	QDateTime current = QDateTime::currentDateTime();
	return dstActivatedAt(current);
}

void Timezone::SaveConfig()
{
	QFile file(configFileName);
	if (!file.open(QIODevice::WriteOnly))
	{
		LogError(QString("Cannot open config file for writing : %1").arg(configFileName));
		return;
	}
	QDataStream out(&file);
	out.setVersion(QDataStream::Qt_4_3);
	out << area << location << stdCode << stdName << stdOffset << dstCode << dstName << dstOffset << dstList << aliases;
}

/*******/
/* API */
/*******/

void Timezone::InitApiCalls()
{
	DECLARE_API_CALL("gettimezone()", &Timezone::Api_getTimezone);
	DECLARE_API_CALL("settimezone()", &Timezone::Api_setTimezone);

	DECLARE_API_CALL("getdstlist()", &Timezone::Api_getDstList);
	DECLARE_API_CALL("adddst(start,end)", &Timezone::Api_addDst);
	DECLARE_API_CALL("removedst(start,end)", &Timezone::Api_removeDst);

	DECLARE_API_CALL("alias()", &Timezone::Api_Alias);
}

API_CALL(Timezone::Api_Alias)
{
/*
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));
*/
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for %2", account).arg("action", "timezone"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiList(aliases);
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("alias"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for %2", account).arg("alias", "timezone"));

		QString alias = hRequest.GetArg("alias");
		if(!aliases.contains(alias))
		{
			AddAlias(alias);
			SaveConfig();
			return new ApiManager::ApiOk(Translator::tr("Alias '%1' added for timezone '%2'", account).arg(alias, GetZone()));
		}
		return new ApiManager::ApiError(Translator::tr("Alias '%1' already exists for timezone '%2'", account).arg(alias, GetZone()));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("alias"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for %2", account).arg("alias", "timezone"));

		QString alias = hRequest.GetArg("alias");
		if(aliases.contains(alias))
		{
			RemoveAlias(alias);
			SaveConfig();
			return new ApiManager::ApiOk(Translator::tr("Alias '%1' removed for timezone '%2'", account).arg(alias, GetZone()));
		}
		return new ApiManager::ApiError(Translator::tr("Alias '%1' does not exist for timezone '%2'", account).arg(alias, GetZone()));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for %2", account).arg("action", "timezone"));
	}
}

API_CALL(Timezone::Api_getTimezone)
{
	Q_UNUSED(account);
	Q_UNUSED(hRequest);

	QMap<QString, QVariant> list;

	list.insert("area", area);
	list.insert("location", location);
	list.insert("stdcode", stdCode);
	list.insert("stdname", stdName);
	list.insert("stdoffset", stdOffset);
	list.insert("dstcode", dstCode);
	list.insert("dstname", dstName);
	list.insert("dstoffset", dstOffset);

	return new ApiManager::ApiMappedList(list);
}

API_CALL(Timezone::Api_setTimezone)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(hRequest.HasArg("stdCode"))
	{
		SetStdCode(hRequest.GetArg("stdCode"));
	}
	if(hRequest.HasArg("stdName"))
	{
		SetStdName(hRequest.GetArg("stdName"));
	}
	if(hRequest.HasArg("stdOffset"))
	{
		SetStdOffset(hRequest.GetArg("stdOffset").toFloat());
	}
	if(hRequest.HasArg("dstCode"))
	{
		SetDstCode(hRequest.GetArg("dstCode"));
	}
	if(hRequest.HasArg("dstName"))
	{
		SetDstName(hRequest.GetArg("dstName"));
	}
	if(hRequest.HasArg("dstOffset"))
	{
		SetDstOffset(hRequest.GetArg("dstOffset").toFloat());
	}
	SaveConfig();

	return new ApiManager::ApiOk(Translator::tr("Timezone updated", account));
}

API_CALL(Timezone::Api_getDstList)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	return new ApiManager::ApiList(dstList);
}

API_CALL(Timezone::Api_addDst)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString s = hRequest.GetArg("start");
	QString e = hRequest.GetArg("end");
	if(!dstList.contains(s + "|" + e))
	{
		dstList.append(s + "|" + e);
		SaveConfig();
		return new ApiManager::ApiOk(Translator::tr("Dst added", account));
	}
	return new ApiManager::ApiError(Translator::tr("Dst already exists", account));
}

API_CALL(Timezone::Api_removeDst)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString s = hRequest.GetArg("start");
	QString e = hRequest.GetArg("end");
	if(dstList.contains(s + "|" + e))
	{
		dstList.removeAll(s + "|" + e);
		SaveConfig();
		return new ApiManager::ApiOk(Translator::tr("Dst removed", account));
	}
	return new ApiManager::ApiError(Translator::tr("Dst doesn't exist", account));
}

