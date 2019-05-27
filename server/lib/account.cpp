#include <QCryptographicHash>
#include <QCoreApplication>
#include <QDataStream>
#include <QFlag>
#include "account.h"
#include "settings.h"
#include "log.h"

Account::Account()
{
	SetDefault();
}

Account::Account(SpecialAccount t)
{
	switch(t)
	{
		case Guest:
			SetDefault(); // Default values
			login = "guest";
			username = "Guest";
			break;

		case DefaultAdmin:
			SetDefault(); // Default values
			login = "admin";
			username = "Administrator";
			passwordHash = QCryptographicHash::hash("admin", QCryptographicHash::Md5);
			isAdmin = true;
			break;
	}
}

Account::Account(QDataStream & in, unsigned int version)
{
	SetDefault();
	if(version == 1)
	{
		in >> login >> username >> passwordHash >> isAdmin >> UserAccess >> listOfBunnies >> listOfZtamps;
		abuseCount = 0;
		startBan = QDateTime::currentDateTime();
		needSave = true;
	}
	else if(version == 2)
	{
		in >> login >> username >> passwordHash >> language >> email >> isAdmin >> UserAccess >> listOfBunnies >> listOfZtamps;
		abuseCount = 0;
		startBan = QDateTime::currentDateTime();
		needSave = true;
	}
	else if(version == 3)
	{
		in >> login >> username >> passwordHash >> language >> email >> isAdmin >> isPremium >> isVip >> loginCount >> lastLogin >> UserAccess >> listOfBunnies >> listOfZtamps;
		abuseCount = 0;
		startBan = QDateTime::currentDateTime();
		needSave = true;
	}
	else if(version == 4)
	{
		in >> login >> username >> passwordHash >> language >> email >> isAdmin >> isPremium >> isVip >> loginCount >> lastLogin >> abuseCount >> startBan >> UserAccess >> listOfBunnies >> listOfZtamps;
		//if(abuseCount > 5) {
		//	abuseCount = 0;
		//	startBan = QDateTime::currentDateTime();
		//	needSave = true;
		//}
	}
	else
		LogError(QString("Can't load account with version %1").arg(version));
}

Account::Account(QString const& l, QString const& u, QByteArray const& p)
{
	SetDefault();
	login = l;
	username = u;
	passwordHash = p;
	language = "fr";
	email = "";
	abuseCount = 0;
	startBan = QDateTime::currentDateTime();
	UserAccess[AcGlobal] = Read;
	UserAccess[AcAccount] = ReadWrite;
	UserAccess[AcBunnies] = ReadWrite;
	UserAccess[AcZtamps] = ReadWrite;
	UserAccess[AcPlugins] = Read;
	UserAccess[AcPluginsBunny] = ReadWrite;
	UserAccess[AcPluginsZtamp] = ReadWrite;
	needSave = true;
}

Account::Account(QString const& l, QString const& u, QByteArray const& p, QString const& lng)
{
	SetDefault();
	login = l;
	username = u;
	passwordHash = p;
	language = lng;
	email = "";
	abuseCount = 0;
	startBan = QDateTime::currentDateTime();
	UserAccess[AcGlobal] = Read;
	UserAccess[AcAccount] = ReadWrite;
	UserAccess[AcBunnies] = ReadWrite;
	UserAccess[AcZtamps] = ReadWrite;
	UserAccess[AcPlugins] = Read;
	UserAccess[AcPluginsBunny] = ReadWrite;
	UserAccess[AcPluginsZtamp] = ReadWrite;
	needSave = true;
}

Account::Account(QString const& l, QString const& u, QByteArray const& p, QString const& lng, QString const& m)
{
	SetDefault();
	login = l;
	username = u;
	passwordHash = p;
	language = lng;
	email = m;
	abuseCount = 0;
	startBan = QDateTime::currentDateTime();
	UserAccess[AcGlobal] = Read;
	UserAccess[AcAccount] = ReadWrite;
	UserAccess[AcBunnies] = ReadWrite;
	UserAccess[AcZtamps] = ReadWrite;
	UserAccess[AcPlugins] = Read;
	UserAccess[AcPluginsBunny] = ReadWrite;
	UserAccess[AcPluginsZtamp] = ReadWrite;
	needSave = true;
}

void Account::SetDefault()
{
	// By default NO ACCESS
	needSave = false;
	isAdmin = false;
	isPremium = false;
	isVip = false;
	loginCount = 0;
	lastLogin = QDateTime();
	abuseCount = 0;
	startBan = QDateTime::currentDateTime();
	UserAccess.insert(AcGlobal,None);
	UserAccess.insert(AcAccount,None);
	UserAccess.insert(AcBunnies,None);
	UserAccess.insert(AcZtamps,None);
	UserAccess.insert(AcPluginsBunny,None);
	UserAccess.insert(AcPluginsZtamp,None);
	UserAccess.insert(AcPlugins,None);
	UserAccess.insert(AcServer,None);
}

QDataStream & operator<< (QDataStream & out, const Account & a)
{
	out << a.login << a.username << a.passwordHash << a.language << a.email << a.isAdmin << a.isPremium << a.isVip << a.loginCount << a.lastLogin << a.abuseCount << a.startBan << a.UserAccess << a.listOfBunnies << a.listOfZtamps;
	return out;
}

QDataStream & operator>> (QDataStream & in, Account::Rights & r)
{
	int value;
	in >> value;
	r = QFlag(value);
	return in;
}

QDataStream & operator<< (QDataStream & out, const Account::Rights & r)
{
	out << (int)r;
	return out;
}

QDir * Account::GetUserDir()
{
	QString accountName = QCryptographicHash::hash(login.toLatin1(), QCryptographicHash::Md5).toHex();
	QDir userDir(GlobalSettings::GetString("Config/RealHttpRoot"));
	if (!userDir.cd("users"))
	{
		if (!userDir.mkdir("users"))
		{
			LogError(QString("Unable to create users directory !\n"));
		}
		userDir.cd("users");
	}
	if (!userDir.cd(accountName))
	{
		if (!userDir.mkdir(accountName))
		{
			LogError(QString("Unable to create " + accountName + " directory !\n"));
		}
		userDir.cd(accountName);
	}
	QStringList filters;
	filters << "*.mp3";
	userDir.setNameFilters(filters);
	return new QDir(userDir);
}

