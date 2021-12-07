#ifndef _PLUGINEARS_H_
#define _PLUGINEARS_H_

#include "plugininterface.h"

class PluginEars
	: public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.ears" )

public:
	PluginEars();

	virtual bool OnEarsMove(Bunny *, int, int) override;
	virtual const QString GetVersion(void) override { return "1.0.1"; }

private:
	virtual ~PluginEars() = default;

	// API
	virtual void InitApiCalls(void) override;
	PLUGIN_BUNNY_API_CALL(Api_Friend);
	PLUGIN_BUNNY_API_CALL(Api_getFriend);
	PLUGIN_BUNNY_API_CALL(Api_setFriend);
	PLUGIN_BUNNY_API_CALL(Api_checkFriend);
};

#endif
