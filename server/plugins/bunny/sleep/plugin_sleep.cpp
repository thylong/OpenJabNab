#include <QDate>
#include <QMap>
#include "plugin_sleep.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "nabaztagmanager.h"
#include "cron.h"
#include "log.h"
#include "messagepacket.h"
#include "packet.h"
#include "settings.h"
#include "sleeppacket.h"
#include "translator.h"

PluginSleep::PluginSleep():PluginInterface("sleep", "Advanced sleep and wake up",BunnyV1Plugin | BunnyV2Plugin | CronPlugin | SingleClickPlugin | DoubleClickPlugin | RfidPlugin | EarsPlugin | VoicePlugin) {}

PluginSleep::~PluginSleep()
{
	Cron::UnregisterAll(this);
}

bool PluginSleep::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if(getPertinence(Translator::tr("sleep", b), command))
	{
		return true;
	}
	return false;
}

bool PluginSleep::OnRFID(Bunny * b, QByteArray const& tag)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
	QString rfid = QString(tag.toHex());
	if(list.contains(rfid))
	{
		QString action = list.value(rfid).toString();
		return true;
	}
	return false;
}

void PluginSleep::SetServices(Bunny * b)
{
	bool sleep = NeedToSleep(b);
	//LogDebug(QString("Nabaztag %1 needs to sleep: %2 !").arg("Bunny").arg(sleep ? "yes" : "no"));
	b->SetGlobalSetting("asleep", sleep);
	b->SetService(13, sleep ? 0x02 : 0x00);
}
/*
void PluginSleep::SetServicesImportant(Bunny * b)
{
	if(NeedToSleep(b))
	{
		if(b->GetGlobalSetting("asleep", false).toBool() == false)
		{
			b->SetGlobalSetting("asleep", true);
		}
		b->SetService(9, 0);
		b->SetService(NabaztagManager::LeftEar, 10);
		b->SetService(NabaztagManager::RightEar, 10);
		b->SetService(1, -1);
		b->SetService(2, -1);
		b->SetService(3, -1);
		b->SetService(4, -1);
	}
	else
	{
		if(b->GetGlobalSetting("asleep", false).toBool() == true)
		{
			b->SetGlobalSetting("asleep", false);
		}
		b->SetService(NabaztagManager::LeftEar, b->GetLeftEar());
		b->SetService(NabaztagManager::RightEar, b->GetRightEar());
	}
}
*/
/*
QString PluginSleep::SpecialBytecode(Bunny * b)
{
	if(NeedToSleep(b))
	{
		return QString("srcSleep");
	}
	return QString();
}
*/
bool PluginSleep::OnClick(Bunny * , PluginInterface::ClickType )
{
	return false;
}

bool PluginSleep::OnEarsMove(Bunny * b, int , int )
{
	if(b->IsSleeping() && b->GetPluginSetting(GetName(), "WakeupOnEars", false).toBool())
	{
		b->SendPacket(SleepPacket(SleepPacket::Wake_Up), GetName());
		return true;
	}
	return false;
}
void PluginSleep::OnBunnyConnect(Bunny * b)
{
	b->SetGlobalSetting("asleep", false);
	ConvertConf(b);
	RegisterCrons(b);
}

void PluginSleep::OnBunnyDisconnect(Bunny * b)
{
	//b->SetGlobalSetting("asleep", b->IsSleeping());
	CleanCrons(b);
}

void PluginSleep::ConvertConf(Bunny * b)
{
	QList<QVariant> wakeupList = b->GetPluginSetting(GetName(), QString("wakeupList"), QList<QVariant>()).toList();
	QList<QVariant> sleepList = b->GetPluginSetting(GetName(), QString("sleepList"), QList<QVariant>()).toList();

	if(wakeupList.count() == 0 && sleepList.count() == 0) // Nothing configured, nothing to do
		return;

	if(wakeupList.count() != 7 || sleepList.count() != 7) // Error :/
	{
		return;
	}

	QList<SleepTime> sleeplist;
	for(int day = 0; day < 6; day++)
	{
		SleepTime sleep;
		sleep.sleepAt = sleepList.at(day).toTime();
		sleep.sleepOn = day+1;
		sleep.wakeAt = wakeupList.at(day+1).toTime();
		sleep.wakeOn = day+2;

		sleeplist = addSleepTime(sleeplist, sleep);
	}
	SleepTime sleep;
	sleep.sleepAt = sleepList.at(6).toTime();
	sleep.sleepOn = 7;
	sleep.wakeAt = wakeupList.at(0).toTime();
	sleep.wakeOn = 1;

	sleeplist = addSleepTime(sleeplist, sleep);
	b->SetPluginSetting(GetName(), QString("SleepList"), fromSleepTimes(sleeplist));
	b->RemovePluginSetting(GetName(), QString("wakeupList"));
	b->RemovePluginSetting(GetName(), QString("sleepList"));
}

bool PluginSleep::NeedToSleep(const Bunny * b)
{
	if(b->GetGlobalSetting("asleep", false).toBool())
		return true;

	QDateTime currentDateTime = Translator::GetCurrentTime(b->GetGlobalSetting("TimeZone","UTC").toString());

	QList<SleepTime> sleeps = getSleepTimes(b->GetPluginSetting(GetName(), QString("SleepList"), QStringList()).toStringList());
	foreach(SleepTime sleep, sleeps)
	{
		if(sleep.IsSleepingAt(currentDateTime))
			return true;
	}
	return false;
}

void PluginSleep::OnInitPacket(const Bunny * b, AmbientPacket &, SleepPacket & s)
{
	if (NeedToSleep(b))
		s.SetState(SleepPacket::Sleep);
	else
		s.SetState(SleepPacket::Wake_Up);
}

void PluginSleep::UpdateState(Bunny * b)
{
	bool wakeup = !NeedToSleep(b);

	if (wakeup && b->IsSleeping())
		b->SendPacket(SleepPacket(SleepPacket::Wake_Up), GetName());
	else if(!wakeup && b->IsIdle())
		b->SendPacket(SleepPacket(SleepPacket::Sleep), GetName());
}

void PluginSleep::OnCronWakeUp(Bunny * bunny, QVariant, unsigned int)
{
	if(!bunny->IsSleeping())
	{
		bunny->SendPacket(SleepPacket(SleepPacket::Wake_Up), GetName());
		LogWarning(QString("WakeUp but not sleeping (%1)").arg(QString(bunny->GetXmppResource())));
		return;
	}
	bunny->SendPacket(SleepPacket(SleepPacket::Wake_Up), GetName());
}


void PluginSleep::OnCronSleep(Bunny * bunny, QVariant, unsigned int)
{
	if(!bunny->IsIdle())
	{
		bunny->SendPacket(SleepPacket(SleepPacket::Sleep), GetName());
		LogWarning(QString("Sleep but not idle (%1)").arg(QString(bunny->GetXmppResource())));
		return;
	}
	bunny->SendPacket(SleepPacket(SleepPacket::Sleep), GetName());
}

void PluginSleep::CleanCrons(Bunny * b)
{
	Cron::UnregisterAllForBunny(this, b);
}

void PluginSleep::RegisterCrons(Bunny * b)
{
	QList<SleepTime> sleeps = getSleepTimes(b->GetPluginSetting(GetName(), QString("SleepList"), QStringList()).toStringList());
	foreach(SleepTime sleep, sleeps)
	{
		Cron::RegisterWeekly(this, (Qt::DayOfWeek)(sleep.wakeOn), sleep.wakeAt, b, Cron::Classic, QVariant(), "OnCronWakeUp");
		Cron::RegisterWeekly(this, (Qt::DayOfWeek)(sleep.sleepOn), sleep.sleepAt, b, Cron::Classic, QVariant(), "OnCronSleep");
	}
}

SleepTime PluginSleep::getSleepTime(QString string)
{
	SleepTime sleep;
	QStringList s = string.split("|");
	if(s.size() == 4)
	{
		sleep.sleepAt = QTime::fromString(s.at(0), "hh:mm");
		sleep.sleepOn = s.at(1).toInt();
		sleep.wakeAt = QTime::fromString(s.at(2), "hh:mm");
		sleep.wakeOn = s.at(3).toInt();
	}
	return sleep;
}

QString PluginSleep::fromSleepTime(SleepTime sleep)
{
	QString string;
	if(sleep.valid())
	{
		string = sleep.sleepAt.toString("hh:mm") + "|" + QString::number(sleep.sleepOn) + "|";
		string += sleep.wakeAt.toString("hh:mm") + "|" + QString::number(sleep.wakeOn);
	}
	return string;
}

QList<SleepTime> PluginSleep::getSleepTimes(QStringList stringlist)
{
	QList<SleepTime> sleeplist;
	foreach(QString string, stringlist)
	{
		sleeplist.append(getSleepTime(string));
	}
	return sleeplist;
}

QStringList PluginSleep::fromSleepTimes(QList<SleepTime> sleeplist)
{
	QStringList stringlist;
	foreach(SleepTime sleep, sleeplist)
	{
		stringlist.append(fromSleepTime(sleep));
	}
	return stringlist;
}

QList<SleepTime> PluginSleep::addSleepTime(QList<SleepTime> sleeplist, SleepTime sleep)
{
	sleeplist.append(sleep);
	sleeplist = compactSleepTime(sleeplist);
	return sleeplist;
}

QList<SleepTime> PluginSleep::compactSleepTime(QList<SleepTime> sleeplist)
{
	if(sleeplist.size() > 1)
	{
		std::sort(sleeplist.begin(), sleeplist.end());
		QList<SleepTime> sleeplistcompact;
		for(int k = 0; k < sleeplist.size(); k++)
		{
			SleepTime current = sleeplist.at(k);
			if(k < sleeplist.size() - 1)
			{
				SleepTime next = sleeplist.at(k+1);
				// Next sleep begins before current sleep ends
				if(current.WakeOn() > next.sleepOn || (current.WakeOn() == next.sleepOn && current.wakeAt >= next.sleepAt))
				{
					// Next sleep begins and ends before current sleep ends
					if(current.WakeOn() > next.WakeOn() || (current.WakeOn() == next.WakeOn() && current.wakeAt >= next.wakeAt))
					{
						sleeplist.removeAt(k+1);
						// skipping next sleep;
					}
					else
					{
						// Merging to sleeps
						current.wakeOn = next.wakeOn;
						current.wakeAt = next.wakeAt;
						sleeplist.insert(k, current);
						sleeplist.removeAt(k+1);
					}
					return compactSleepTime(sleeplist);
					//k++;
				}
				sleeplistcompact.append(current);
			}
			else
			{
				SleepTime next = sleeplist.at(0);
				// Next sleep begins before current sleep ends
				if(current.WakeOn() > next.sleepOn+7 || (current.WakeOn() == next.sleepOn+7 && current.wakeAt >= next.sleepAt))
				{
					// Next sleep begins and ends before current sleep ends
					if(current.WakeOn() > next.WakeOn()+7 || (current.WakeOn() == next.WakeOn()+7 && current.wakeAt >= next.wakeAt))
					{
						sleeplist.removeAt(0);
						// skipping next sleep;
					}
					else
					{
						// Merging to sleeps
						current.wakeOn = next.wakeOn;
						current.wakeAt = next.wakeAt;
						sleeplist.insert(k, current);
						sleeplist.removeAt(0);
					}
					return compactSleepTime(sleeplist);
					//k++;
				}
				sleeplistcompact.append(current);
			}
		}
		return sleeplistcompact;
	} else {
		return sleeplist;
	}
}
/*******
 * API *
 *******/

void PluginSleep::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("sleep()", &PluginSleep::Api_Sleep);
//	DECLARE_PLUGIN_BUNNY_API_CALL("wakeup()", &PluginSleep::Api_Wakeup);
//	DECLARE_PLUGIN_BUNNY_API_CALL("setup(wakeupList,sleepList)", &PluginSleep::Api_Setup);
//	DECLARE_PLUGIN_BUNNY_API_CALL("getsetup()", &PluginSleep::Api_GetSetup);
	DECLARE_PLUGIN_BUNNY_API_CALL("config()", &PluginSleep::Api_Config);
	DECLARE_PLUGIN_BUNNY_API_CALL("rfid()", &PluginSleep::Api_RFID);
}

PLUGIN_BUNNY_API_CALL(PluginSleep::Api_RFID)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		if(!hRequest.HasArg("name"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(!list.contains(tag))
		{
			list.insert(tag, name);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Associate(bunny, this);
			return new ApiAnswers::Ok(Translator::tr("Add RFID '%1' for bunny '%2'").arg(tag, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("RFID '%1' already assigned to bunny '%2'", account).arg(tag, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("tag"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tag", GetName()));

		QString tag = hRequest.GetArg("tag");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "RFID", QMap<QString, QVariant>()).toMap();
		if(list.contains(tag))
		{
			list.remove(tag);
			bunny->SetPluginSetting(GetName(), "RFID", list);
			Ztamp * z = ZtampManager::GetZtamp(tag.toLatin1());
			z->Dissociate(bunny);

			return new ApiAnswers::Ok(Translator::tr("RFID '%1' removed for bunny '%2'", account).arg(tag, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("RFID '%1' is not assign to bunny '%2'", account).arg(tag, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginSleep::Api_Config)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	bool noReload = false;
	if(hRequest.HasArg("noreload"))
		noReload = true;

	if(action == "ears")
	{
		if(hRequest.HasArg("set"))
		{
			bool ears = (bool)hRequest.GetArg("set").toInt();
			bunny->SetPluginSetting(GetName(), "WakeupOnEars", ears);
			return new ApiAnswers::Ok(Translator::tr("Ear setup done", account));
		}
		else
		{
			return new ApiAnswers::String(bunny->GetPluginSetting(GetName(), "WakeupOnEars", false).toString());
		}
	}
	else if(action == "list")
	{
		ConvertConf(bunny);
		return new ApiAnswers::List(bunny->GetPluginSetting(GetName(), QString("SleepList"), QStringList()).toStringList());
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("sleepAt"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sleepAt", GetName()));

		QTime sleepAt = Cron::mkTime(hRequest.GetArg("sleepAt"));
		if(!sleepAt.isValid())
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("sleepAt", GetName()));

		if(!hRequest.HasArg("sleepOn"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sleepOn", GetName()));

		int sleepOn = ((hRequest.GetArg("sleepOn").toInt() - 1 ) % 7) + 1;

		if(!hRequest.HasArg("wakeAt"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("wakeAt", GetName()));

		QTime wakeAt = Cron::mkTime(hRequest.GetArg("wakeAt"));
		if(!wakeAt.isValid())
			return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("wakeAt", GetName()));

		if(!hRequest.HasArg("wakeOn"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("wakeOn", GetName()));

		int wakeOn = ((hRequest.GetArg("wakeOn").toInt() - 1 ) % 7) + 1;

		SleepTime sleep;
		sleep.sleepAt = sleepAt;
		sleep.sleepOn = sleepOn;
		sleep.wakeAt = wakeAt;
		sleep.wakeOn = wakeOn;

		QList<SleepTime> sleeplist = getSleepTimes(bunny->GetPluginSetting(GetName(), QString("SleepList"), QStringList()).toStringList());
		sleeplist = addSleepTime(sleeplist, sleep);
		bunny->SetPluginSetting(GetName(), QString("SleepList"), fromSleepTimes(sleeplist));

		if(!noReload)
		{
			CleanCrons(bunny);
			RegisterCrons(bunny);
			UpdateState(bunny);
		}

		return new ApiAnswers::Ok(Translator::tr("Settings saved for plugin %2", account).arg(GetName()));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("id"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("id", GetName()));

		int id = hRequest.GetArg("id").toInt();

		QList<SleepTime> sleeplist = getSleepTimes(bunny->GetPluginSetting(GetName(), QString("SleepList"), QStringList()).toStringList());
		sleeplist.removeAt(id);
		bunny->SetPluginSetting(GetName(), QString("SleepList"), fromSleepTimes(sleeplist));

		if(!noReload)
		{
			CleanCrons(bunny);
			RegisterCrons(bunny);
			UpdateState(bunny);
		}

		return new ApiAnswers::Ok(Translator::tr("Settings saved for plugin %2", account).arg(GetName()));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginSleep::Api_Sleep)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "sleep")
	{
		if(!bunny->IsIdle())
			return new ApiAnswers::Error(Translator::tr("Bunny is not idle (%1)", account).arg(QString(bunny->GetXmppResource())));

		bunny->SendPacket(SleepPacket(SleepPacket::Sleep), GetName());
		bunny->SetGlobalSetting("asleep", true);
		return new ApiAnswers::Ok(Translator::tr("Bunny is going to sleep.", account));
	}
	else if(action == "wakeup")
	{
		if(!bunny->IsSleeping())
			return new ApiAnswers::Error(Translator::tr("Bunny is not sleeping (%1)", account).arg(QString(bunny->GetXmppResource())));

		bunny->SendPacket(SleepPacket(SleepPacket::Wake_Up), GetName());
		bunny->SetGlobalSetting("asleep", false);
		return new ApiAnswers::Ok(Translator::tr("Bunny is waking up.", account));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
