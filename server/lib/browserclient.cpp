#include <QCoreApplication>
#include <QNetworkCacheMetaData>
#include <QNetworkReply>
#include <QDateTime>
#include "browsercache.h"
#include "browserclient.h"
#include "QsLog.h"
#include "log.h"
#include "settings.h"
// Cache-Control: max-age=3600

BrowserClient::BrowserClient ( QObject * parent)
: QNetworkAccessManager(parent)
, _age(3600)
, _diskCache(this)
{
	//QNetworkDiskCache * diskCache = BrowserCache::GetCache();
  QString cachePath = GlobalSettings::GetString("Directories/NetworkCacheDir",
                    QCoreApplication::applicationDirPath().append("/cache/"));
	_diskCache.setCacheDirectory(QDir(cachePath).absolutePath());

	setCache(&_diskCache);
}

QNetworkReply* BrowserClient::createRequest(QNetworkAccessManager::Operation operation, const QNetworkRequest &req, QIODevice *device)
{
	QNetworkRequest request(req);
	QNetworkCacheMetaData meta = _diskCache.metaData(request.url());
	if(_age > 0)
	{
		if(meta.expirationDate().secsTo(QDateTime::currentDateTime()) > 0) {
			QsLogging::Logger::DebugLog(QString("Want %1 forced to revalidate with max-_age %2").arg(request.url().toString(), QString::number(_age)), "BrowserClient");
			request.setRawHeader("Pragma", "no-cache");
			request.setRawHeader("Cache-Control", "no-cache, must-revalidate");
			meta.setExpirationDate(QDateTime::currentDateTime().addSecs(_age));
			_diskCache.updateMetaData(meta);
			request.setAttribute(QNetworkRequest::CacheLoadControlAttribute, QNetworkRequest::AlwaysNetwork);
		}
		else
		{
			QsLogging::Logger::DebugLog(QString("Want %1 with max-_age %2").arg(request.url().toString(), QString::number(_age)), "BrowserClient");
			request.setRawHeader("Cache-Control", "max-_age=" + QString::number(_age).toLatin1() );
			request.setAttribute(QNetworkRequest::CacheLoadControlAttribute, QNetworkRequest::PreferCache);
		}
	}
	else
	{
		QsLogging::Logger::DebugLog(QString("Want %1 from network").arg(request.url().toString()), "BrowserClient");
		request.setAttribute(QNetworkRequest::CacheLoadControlAttribute, QNetworkRequest::AlwaysNetwork);
	}

	auto* reply = QNetworkAccessManager::createRequest(operation, request, device);
	bool fromCache = reply->attribute(QNetworkRequest::SourceIsFromCacheAttribute).toBool();
	if(fromCache)
	{
		QsLogging::Logger::DebugLog(QString("Using cache for %1").arg(request.url().toString()), "BrowserClient");
	}
	return reply;
}
