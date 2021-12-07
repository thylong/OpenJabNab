#ifndef _ZTAMPMANAGER_H_
#define _ZTAMPMANAGER_H_

#include <QHash>

#include "global.h"
#include "apihandler.h"
#include "ztamp.h"

class PluginInterface;
class Ztamp;

class OJN_EXPORT ZtampManager
	: public ApiHandler<ZtampManager>
{
	friend class PluginAuth;
	friend class ApiManager;
	friend class PluginManager;
public:
	static ZtampManager & Instance();
	static Ztamp * GetZtamp(QByteArray const&);
	static Ztamp * GetZtamp(PluginInterface *, QByteArray const&);
	static void PluginStateChanged(PluginInterface *);
	static inline void Init() { InitApiCalls(); };
	static inline void LoadZtamps() { Instance().LoadAllZtamps(); }
	static inline void SaveZtamps() { Instance().SaveAllZtamps(); }
	static void Close();

	int GetZtampCount();

protected:
	static QVector<Ztamp *> GetZtamps();
	static void PluginLoaded(PluginInterface *);
	static void PluginUnloaded(PluginInterface *);

	// API
	static void InitApiCalls();
	API_CALL(Api_GetListOfZtamps);
	API_CALL(Api_GetListOfAllZtamps);
	API_CALL(Api_RemoveZtamp);

private:
	ZtampManager();
	void LoadAllZtamps();
	void SaveAllZtamps();
	QDir ztampsDir;
	static QHash<QByteArray, Ztamp *> listOfZtamps;
};

#endif
