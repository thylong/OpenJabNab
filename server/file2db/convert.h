#ifndef _OPENJABNAB_H_
#define _OPENJABNAB_H_

#include <QCoreApplication>
#include "pluginmanager.h"

class File2Db : public QCoreApplication
{
	Q_OBJECT

public:
	File2Db(int argc, char ** argv);
	void Close();
	virtual ~File2Db();

signals:
	void Quit();
private:
	void insertServerInDb();
	void convertAccounts();
	void convertBunnies();
	void convertZtamps();
	void Log(QString const&);

	bool update;
};

#endif
