#ifndef _PLUGINEARS_H_
#define _PLUGINEARS_H_

#include "plugininterface.h"

class PluginEars : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.ears" )

public:
	PluginEars();
	virtual ~PluginEars();
	bool OnEarsMove(Bunny *, int, int);
	QString GetVersion() { return "1.0.1"; }

	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_getFriend);
	PLUGIN_BUNNY_API_CALL(Api_setFriend);
	PLUGIN_BUNNY_API_CALL(Api_checkFriend);

	PLUGIN_BUNNY_API_CALL(Api_Friend);
};

#endif
