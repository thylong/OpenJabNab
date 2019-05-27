#ifndef _DBMANAGER_H_
#define _DBMANAGER_H_

#include <QtSql/QtSql>
#include "global.h"

class OJN_EXPORT DbManager
{
public:
	static DbManager & Instance();

	static void Close();
	static QSqlDatabase getDb();
	static QSqlDatabase getOpenDb();
	static bool openDbIfNeeded();
	static void releaseDb();
	static void Init();

private:
	DbManager();
	void createTables();
	static QSqlDatabase db;
};

inline void DbManager::Init()
{
}

#endif
