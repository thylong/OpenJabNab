#ifndef _BROWSERCACHE_H_
#define _BROWSERCACHE_H_

#include <QDir>
#include <QCoreApplication>
#include <QNetworkDiskCache>
#include "global.h"

class OJN_EXPORT BrowserCache : public QObject
{
	Q_OBJECT
public:
	// General
        static BrowserCache & Instance();

	BrowserCache();
	static QNetworkDiskCache * GetCache();
	static void Init(QObject *);
	static void Close();

protected:
	virtual ~BrowserCache();

private:
	QNetworkDiskCache diskCache;
};

#endif
