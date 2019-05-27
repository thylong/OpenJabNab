#include <QDebug>
#include "dbmanager.h"
#include "settings.h"
#include "log.h"

QSqlDatabase DbManager::db;

DbManager::DbManager()
{
        db = QSqlDatabase::addDatabase("QMYSQL", "sql_db");
        db.setHostName(GlobalSettings::GetString("Database/Hostname", "127.0.0.1"));
        db.setDatabaseName(GlobalSettings::GetString("Database/DBName", "ojn"));
        db.setUserName(GlobalSettings::GetString("Database/User", "ojn"));
        db.setPassword(GlobalSettings::GetString("Database/Pass", "ojn"));
        db.setPort(GlobalSettings::GetInt("Database/Port", 3306));

        //db.setHostName("127.0.0.1");
        //db.setDatabaseName("ojn");
        //db.setUserName("ojn");
        //db.setPassword("ojnpass");
        bool ok = db.open();
        if(!ok)
	{
		LogError("Unable to initialize database !\n");
		exit(-1);
	}
        else
	{
		createTables();
		db.close();
	}
}

QSqlDatabase DbManager::getOpenDb()
{
	if(!Instance().db.isOpen())
	{
        	bool ok = Instance().db.open();
        	if(!ok)
		{
			LogError("Unable to connect to database !\n");
		}
		return Instance().db;
	}
	return Instance().db;
}

QSqlDatabase DbManager::getDb()
{
	return Instance().db;
}

bool DbManager::openDbIfNeeded()
{
	if(!Instance().db.isOpen())
	{
        	bool ok = Instance().db.open();
        	if(!ok)
		{
			LogError("Unable to connect to database ! : " + Instance().db.lastError().text() + "\n");
		}
		return true;
	}
	return false;
}

void DbManager::releaseDb()
{
	Instance().db.close();
}
 
void DbManager::createTables()
{
	QSqlQuery *query = new QSqlQuery(db);
	if(!db.tables().contains("server"))
	{
		bool ret = query->exec("CREATE TABLE server (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `hostname` VARCHAR(255)) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'server' in DB : %1").arg(query->lastError().driverText()));
			exit(-1);
		}
		query->finish();
	}
	if(!db.tables().contains("account"))
	{
		bool ret = query->exec("CREATE TABLE account (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `username` VARCHAR(64) UNIQUE KEY, `settings` BLOB) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'account' in DB : %1").arg(query->lastError().driverText()));
			exit(-1);
		}
		query->finish();
	}
	if(!db.tables().contains("bunny"))
	{
		bool ret = query->exec("CREATE TABLE bunny (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `server_id` INT, `mac` VARCHAR(12) UNIQUE KEY, `settings` BLOB, `account_id` INT) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'bunny' in DB : %1").arg(query->lastError().driverText()));
			exit(-1);
		}
		query->finish();
	}
	if(!db.tables().contains("ztamp"))
	{
		bool ret = query->exec("CREATE TABLE ztamp (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `server_id` INT, `serial` VARCHAR(16) UNIQUE KEY, `settings` BLOB, `account_id` INT) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'ztamp' in DB : %1").arg(query->lastError().driverText()));
			exit(-1);
		}
		query->finish();
	}
	delete query;
}

DbManager & DbManager::Instance()
{
  static DbManager d;
  return d;
}

void DbManager::Close()
{
	releaseDb();
	Instance().db = QSqlDatabase();
        QSqlDatabase::removeDatabase("sql_db");
}
