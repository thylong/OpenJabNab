#include <QDebug>
#include <QThread>
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

        // Retry database connection for Docker environments
        int retries = 0;
        const int maxRetries = 10;
        bool ok = false;
        
        while(!ok && retries < maxRetries)
        {
                ok = db.open();
                if(!ok)
                {
                        retries++;
                        LogWarning(QString("Database connection attempt %1/%2 failed: %3").arg(retries).arg(maxRetries).arg(db.lastError().text()));
                        if(retries < maxRetries)
                        {
                                LogInfo("Retrying database connection in 2 seconds...");
                                QThread::sleep(2);
                        }
                }
        }
        
        if(!ok)
        {
                LogError("Unable to initialize database after " + QString::number(maxRetries) + " attempts! Server will continue without database functionality.");
                // Don't exit - allow server to continue without database for basic functionality
        }
        else
        {
                LogInfo("Database connection established successfully");
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
			return; // Don't exit - allow server to continue
		}
		query->finish();
	}
	if(!db.tables().contains("account"))
	{
		bool ret = query->exec("CREATE TABLE account (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `username` VARCHAR(64) UNIQUE KEY, `settings` BLOB) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'account' in DB : %1").arg(query->lastError().driverText()));
			return; // Don't exit - allow server to continue
		}
		query->finish();
	}
	if(!db.tables().contains("bunny"))
	{
		bool ret = query->exec("CREATE TABLE bunny (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `server_id` INT, `mac` VARCHAR(12) UNIQUE KEY, `settings` BLOB, `account_id` INT) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'bunny' in DB : %1").arg(query->lastError().driverText()));
			return; // Don't exit - allow server to continue
		}
		query->finish();
	}
	if(!db.tables().contains("ztamp"))
	{
		bool ret = query->exec("CREATE TABLE ztamp (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `server_id` INT, `serial` VARCHAR(16) UNIQUE KEY, `settings` BLOB, `account_id` INT) DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'ztamp' in DB : %1").arg(query->lastError().driverText()));
			return; // Don't exit - allow server to continue
		}
		query->finish();
	}
	
	// Create session_tokens table for persistent authentication tokens
	if(!db.tables().contains("session_tokens"))
	{
		bool ret = query->exec("CREATE TABLE session_tokens ("
			"id INT AUTO_INCREMENT PRIMARY KEY, "
			"token VARCHAR(32) UNIQUE NOT NULL, "
			"username VARCHAR(255) NOT NULL, "
			"expire_time INT UNSIGNED NOT NULL, "
			"created_time INT UNSIGNED NOT NULL, "
			"INDEX idx_token (token), "
			"INDEX idx_username (username), "
			"INDEX idx_expire_time (expire_time)"
			") DEFAULT CHARACTER SET utf8 COLLATE utf8_bin");
		if(!ret)
		{
			LogError(QString("Impossible to create table 'session_tokens' in DB : %1").arg(query->lastError().driverText()));
			return; // Don't exit - allow server to continue
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
