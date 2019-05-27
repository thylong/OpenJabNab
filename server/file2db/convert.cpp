#include <QDebug>
#include <QtSql/QtSql>
#include <QDir>
#include <QFile>

#include <iostream>
#include "convert.h"
#include "accountmanager.h"
#include "bunnymanager.h"
#include "bunny.h"
#include "ztamp.h"
#include "settings.h"

#include "dbmanager.h"

File2Db::File2Db(int argc, char ** argv):QCoreApplication(argc, argv)
{
	update = false;
	GlobalSettings::Init();
	Log("-- File2Db Start --");
	for(int i=0;i<=argc;++i)
	{
		if(QString(argv[i]) == "--force")
		{
			update = true;
		}
	}
	DbManager::Init();
	insertServerInDb();
	
	Bunny::Init();
	Ztamp::Init();

	convertAccounts();
	convertBunnies();
	convertZtamps();
}

void File2Db::insertServerInDb()
{
	QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery query(db);
	query.prepare("SELECT * from `server` WHERE `hostname` = :host");
	query.bindValue(":host", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
	query.exec();
	int size = query.size();
	query.finish();
	if(size < 1)
	{
		query.prepare("INSERT INTO `server` SET `hostname`=:host");
		query.bindValue(":host", GlobalSettings::GetString("OpenJabNab/PingServer"));
		bool ins = query.exec();
		if(!ins)
		{
			Log(QString("Impossible to insert value in table 'server' : %1").arg(query.lastError().driverText()));
			Close();
		}
	}
	DbManager::releaseDb();
}

void File2Db::convertAccounts()
{
        QDir accountsDir = QCoreApplication::applicationDirPath();
        if (accountsDir.cd("accounts"))
        {
	        Log(QString("Finding accounts in : %1").arg(accountsDir.path()));
        	QStringList filters;
        	filters << "*.dat";
        	accountsDir.setNameFilters(filters);
        	QSqlDatabase db = DbManager::getOpenDb();
        	foreach (QFileInfo ffile, accountsDir.entryInfoList(QDir::Files))
        	{
        	        /* Open File */
        	        QByteArray configFileName = accountsDir.absoluteFilePath(ffile.fileName().toLatin1()).toLatin1();
        	        QFile file(configFileName);
        	        if (!file.open(QIODevice::ReadOnly))
        	        {
        	                Log(QString("Cannot open config file for reading : %1").arg(QString(configFileName)));
        	                continue;
        	        }
        	        QDataStream in(&file);
        	        in.setVersion(QDataStream::Qt_4_3);
        	        int version;
        	        in >> version;
        	        Account * a = new Account(in, version);
        	        if (in.status() != QDataStream::Ok)
        	        {
        	                Log(QString("Problem when loading config file for account: %1").arg(QString(configFileName)));
        	                delete a;
        	                continue;
        	        }

			QSqlQuery query(db);
			query.prepare("SELECT * from `account` WHERE `username` = :user");
			query.bindValue(":user", a->GetLogin());
			query.exec();
			int size = query.size();
			query.finish();
			if(size < 1 || update)
			{
				QByteArray byteArray;
				QDataStream stream(&byteArray, QIODevice::WriteOnly);
				stream.setVersion(QDataStream::Qt_4_3);
				stream << Account::Version();
				stream << *a;
				query.prepare("INSERT INTO account SET `username`=:user, `settings`=:settings ON DUPLICATE KEY UPDATE `settings`=:settings_up");
				query.bindValue(":user", a->GetLogin());
				query.bindValue(":settings", byteArray);
				query.bindValue(":settings_up", byteArray);
				bool ret = query.exec();
				if(!ret)
				{
					Log(QString("Impossible to insert account in DB : %1").arg(query.lastError().driverText()));
				}
				else if(update)
				{
					Log(QString("Updating account %1 in DB").arg(a->GetLogin()));
				}
			}
			else
			{
				Log(QString("Account %1 is already in DB").arg(a->GetLogin()));
			}
        	}
		DbManager::releaseDb();
        }
	else
	{
		Log("No accounts to import");
	}
}

void File2Db::convertBunnies()
{
        QDir bunniesDir = QCoreApplication::applicationDirPath();
        if(bunniesDir.cd("bunnies"))
	{
	        Log(QString("Finding bunnies in : %1").arg(bunniesDir.path()));
	        QStringList filters;
	        filters << "*.dat";
	        bunniesDir.setNameFilters(filters);
        	QSqlDatabase db = DbManager::getOpenDb();
	        foreach (QFileInfo ffile, bunniesDir.entryInfoList(QDir::Files))
	        {
        	        QByteArray configFileName = bunniesDir.absoluteFilePath(ffile.fileName().toLatin1()).toLatin1();
        	        QFile file(configFileName);
	                QString bunnyId = ffile.baseName();
		        if (!file.open(QIODevice::ReadOnly))
		        {
		                Log(QString("Cannot open config file for reading : %1").arg(QString(configFileName)));
		                return;
		        }
			QHash<QString, QVariant> GlobalSettings;
			QHash<QString, QHash<QString, QVariant> > PluginsSettings;
			QList<QString> listOfPlugins;
			QHash<QByteArray, QString> knownRFIDTags;

		        QDataStream in(&file);
		        in.setVersion(QDataStream::Qt_4_3);
		        in >> GlobalSettings >> PluginsSettings >> listOfPlugins >> knownRFIDTags;
		        if (in.status() != QDataStream::Ok)
		        {
		                Log(QString("Problem when loading config file for bunny : %1").arg(bunnyId));
		        }


			QSqlQuery query(db);
			query.prepare("SELECT * from `bunny` WHERE `mac` = :mac");
			query.bindValue(":mac", bunnyId);
			query.exec();
			int size = query.size();
			query.finish();
			if(size < 1 || update)
			{
				QByteArray settings;
				QDataStream out(&settings, QIODevice::WriteOnly);
				out.setVersion(QDataStream::Qt_4_3);
				out << GlobalSettings << PluginsSettings << listOfPlugins << knownRFIDTags;
	
				QString owner = GlobalSettings.contains("OwnerAccount") ? GlobalSettings.value("OwnerAccount").toString() : "";
	
				query.prepare("INSERT INTO bunny SET `mac`=:mac, `settings`=:settings, `server_id`=(SELECT `id` FROM server WHERE `hostname`=:host), `account_id`=(SELECT `id` FROM account WHERE `username`=:username) ON DUPLICATE KEY UPDATE `settings`=:settings_up, `server_id`=(SELECT `id` FROM server WHERE `hostname`=:host_up), `account_id`=(SELECT `id` FROM account WHERE `username`=:username_up)");
			//	query.bindValue(":user", a->GetLogin());
				query.bindValue(":mac", bunnyId);
				query.bindValue(":username", owner);
				query.bindValue(":username_up", owner);
				query.bindValue(":settings", settings);
				query.bindValue(":settings_up", settings);
				query.bindValue(":host", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
				query.bindValue(":host_up", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
				bool ret = query.exec();
				if(!ret)
				{
					Log(QString("Impossible to save bunny in DB : %1").arg(query.lastError().driverText()));
				}
				else if(update)
				{
					Log(QString("Updating bunny %1 in DB").arg(bunnyId));
				}
			}
			else
			{
				Log(QString("Bunny %1 is already in DB").arg(bunnyId));
			}
	        }
		DbManager::releaseDb();
	}
}

void File2Db::convertZtamps()
{
        QDir ztampsDir = QCoreApplication::applicationDirPath();
        if(ztampsDir.cd("ztamps"))
	{
	        Log(QString("Finding ztamps in : %1").arg(ztampsDir.path()));
	        QStringList filters;
	        filters << "*.dat";
	        ztampsDir.setNameFilters(filters);
        	QSqlDatabase db = DbManager::getOpenDb();
	        foreach (QFileInfo ffile, ztampsDir.entryInfoList(QDir::Files))
	        {
        	        QByteArray configFileName = ztampsDir.absoluteFilePath(ffile.fileName().toLatin1()).toLatin1();
        	        QFile file(configFileName);
	                QString ztampId = ffile.baseName();
		        if (!file.open(QIODevice::ReadOnly))
		        {
		                Log(QString("Cannot open config file for reading : %1").arg(QString(configFileName)));
		                return;
		        }
			QHash<QString, QVariant> GlobalSettings;
			QHash<QString, QHash<QString, QVariant> > PluginsSettings;
			QList<QString> listOfPlugins;

		        QDataStream in(&file);
		        in.setVersion(QDataStream::Qt_4_3);
		        in >> GlobalSettings >> PluginsSettings >> listOfPlugins;
		        if (in.status() != QDataStream::Ok)
		        {
		                Log(QString("Problem when loading config file for ztamp : %1").arg(ztampId));
		        }


			QSqlQuery query(db);
			query.prepare("SELECT * from `ztamp` WHERE `serial` = :serial");
			query.bindValue(":serial", ztampId);
			query.exec();
			int size = query.size();
			query.finish();
			if(size < 1 || update)
			{
				QByteArray settings;
				QDataStream out(&settings, QIODevice::WriteOnly);
				out.setVersion(QDataStream::Qt_4_3);
				out << GlobalSettings << PluginsSettings << listOfPlugins;
	
				QStringList owner = GlobalSettings.contains("OwnerAccounts") ? GlobalSettings.value("OwnerAccounts").toStringList() : QStringList();
	
				query.prepare("INSERT INTO ztamp SET `serial`=:serial, `settings`=:settings, `server_id`=(SELECT `id` FROM server WHERE `hostname`=:host), `account_id`=(SELECT `id` FROM account WHERE `username`=:username) ON DUPLICATE KEY UPDATE `settings`=:settings_up, `server_id`=(SELECT `id` FROM server WHERE `hostname`=:host_up), `account_id`=(SELECT `id` FROM account WHERE `username`=:username_up)");
			//	query.bindValue(":user", a->GetLogin());
				query.bindValue(":serial", ztampId);
				query.bindValue(":username", owner);
				query.bindValue(":username_up", owner);
				query.bindValue(":settings", settings);
				query.bindValue(":settings_up", settings);
				query.bindValue(":host", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
				query.bindValue(":host_up", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
				bool ret = query.exec();
				if(!ret)
				{
					Log(QString("Impossible to save ztamp in DB : %1").arg(query.lastError().driverText()));
				}
				else if(update)
				{
					Log(QString("Updating ztamp %1 in DB").arg(ztampId));
				}
			}
			else
			{
				Log(QString("Bunny %1 is already in DB").arg(ztampId));
			}
	        }
		DbManager::releaseDb();
	}
}

void File2Db::Close()
{
	emit Quit();
}

File2Db::~File2Db()
{
	GlobalSettings::Close();
	DbManager::Close();
	Log("-- File2Db Close --");
}

void File2Db::Log(QString const& data)
{
	std::cout << qPrintable(data) << std::endl;
}
