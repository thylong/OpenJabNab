#include <QObject>
#include "log.h"
#include "settings.h"
#include "browsercache.h"

BrowserCache::BrowserCache()
{
	diskCache = new QNetworkDiskCache();
  QString cachePath = GlobalSettings::GetString("Directories/NetworkCacheDir",
                  QCoreApplication::applicationDirPath().append("/cache/"));
	diskCache->setCacheDirectory(QDir(cachePath).absolutePath());
}

void BrowserCache::Init(QObject * parent)
{
	Instance().setParent(parent);
}

void BrowserCache::Close()
{
	delete Instance().diskCache;
}

BrowserCache::~BrowserCache()
{
	delete diskCache;
}

BrowserCache & BrowserCache::Instance()
{
	static BrowserCache p;
	return p;
}

QNetworkDiskCache * BrowserCache::GetCache()
{
	return Instance().diskCache;
}
