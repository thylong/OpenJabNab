#ifndef _PLUGINMESSAGEINTERFACE_H_
#define _PLUGINMESSAGEINTERFACE_H_

#include <QDateTime>
#include <QObject>

#include "pluginmanager.h"
#include "bunny.h"

class PluginMessageInterface
{
protected:
	bool SaveMessage(Bunny *, QStringList);
	bool SaveMessage(Bunny *, QString);
	bool SaveMessage(Bunny *, QStringList, int);
	bool SaveMessage(Bunny *, QString, int);
private:
	PluginInterface * GetMessagePlugin();
};

#include "pluginmessageinterface_inline.h"

Q_DECLARE_INTERFACE(PluginMessageInterface,"org.toms.openjabnab.PluginMessageInterface/1.0")

#endif
