#ifndef _BROWSER_H_
#define _BROWSER_H_

#include <QNetworkDiskCache>
#include <QNetworkAccessManager>
#include "global.h"

class OJN_EXPORT BrowserClient
	: public QNetworkAccessManager
{
    Q_OBJECT
public:
	BrowserClient(QObject * parent = 0 );
	inline void setAge(int a = 3600) { _age = a; };

protected:
	QNetworkReply* createRequest(Operation operation, const QNetworkRequest &request, QIODevice *device);

private:
	int _age;
	QNetworkDiskCache _diskCache;
};

#endif
