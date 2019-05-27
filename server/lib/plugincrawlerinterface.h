#ifndef _PLUGINCRAWLERINTERFACE_H_
#define _PLUGINCRAWLERINTERFACE_H_

#include <QByteArray>
#include "plugininterface.h"

//class Bunny;
//class XmppHandler;
class PluginCrawlerInterface
{
public:
	virtual bool DoCrawler(XmppHandler *, QByteArray const&, Bunny **, QByteArray &);
};

inline bool PluginCrawlerInterface::DoCrawler(XmppHandler *, QByteArray const&, Bunny **, QByteArray &)
{
	// No auth available, disconnect
	return false;
}

Q_DECLARE_INTERFACE(PluginCrawlerInterface,"org.toms.openjabnab.PluginCrawlerInterface/1.0")

#endif
