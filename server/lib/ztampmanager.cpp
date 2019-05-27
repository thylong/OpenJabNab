#include <QtSql/QtSql>
#include "account.h"
#include "ztamp.h"
#include "ztampmanager.h"
#include "dbmanager.h"
#include "httprequest.h"
#include "translator.h"

ZtampManager::ZtampManager()
{
}


ZtampManager & ZtampManager::Instance()
{
  static ZtampManager z;
  return z;
}

void ZtampManager::LoadAllZtamps()
{
        QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("SELECT serial FROM ztamp");
	query->exec();
	while(query->next())
	{
		GetZtamp(query->value(0).toString().toLatin1());
	}
	delete query;
	DbManager::releaseDb();
}

void ZtampManager::InitApiCalls()
{
	DECLARE_API_CALL("getListOfZtamps()", &ZtampManager::Api_GetListOfZtamps);
	DECLARE_API_CALL("getListOfAllZtamps()", &ZtampManager::Api_GetListOfAllZtamps);
}

int ZtampManager::GetZtampCount()
{
	return listOfZtamps.count();
}

Ztamp * ZtampManager::GetZtamp(QByteArray const& ztampHexID)
{
	QByteArray ztampID = QByteArray::fromHex(ztampHexID);

	if(listOfZtamps.contains(ztampID))
		return listOfZtamps.value(ztampID);

	Ztamp * z = new Ztamp(ztampID);
	listOfZtamps.insert(ztampID, z);
	return z;
}

Ztamp * ZtampManager::GetZtamp(PluginInterface * p, QByteArray const& ztampHexID)
{
	Ztamp * z = GetZtamp(ztampHexID);

	if(!(p->GetType() & PluginInterface::ZtampPlugin))
		return z;
	if(z->HasPlugin(p))
		return z;
	return NULL;
}

void ZtampManager::Close()
{
	foreach(Ztamp * z, listOfZtamps)
		delete z;
	listOfZtamps.clear();
        QSqlDatabase::removeDatabase("sql_ztamp");
}

QVector<Ztamp *> ZtampManager::GetZtamps()
{
	QVector<Ztamp *> list;
	foreach(Ztamp * z, listOfZtamps)
		list.append(z);
	return list;
}

void ZtampManager::PluginStateChanged(PluginInterface * p)
{
	foreach(Ztamp * z, listOfZtamps)
		z->PluginStateChanged(p);
}

void ZtampManager::PluginLoaded(PluginInterface * p)
{
	foreach(Ztamp * z, listOfZtamps)
		z->PluginLoaded(p);
}

void ZtampManager::PluginUnloaded(PluginInterface * p)
{
	foreach(Ztamp * z, listOfZtamps)
		z->PluginUnloaded(p);
}

API_CALL(ZtampManager::Api_GetListOfZtamps)
{
	Q_UNUSED(hRequest);

	if(!account.HasAccess(Account::AcZtamps,Account::Read))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach(Ztamp * z, listOfZtamps)
		if(account.GetZtampsList().contains(z->GetID()))
			list.insert(z->GetID(), z->GetZtampName());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(ZtampManager::Api_GetListOfAllZtamps)
{
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;

	foreach(Ztamp * z, listOfZtamps)
		list.insert(z->GetID(), z->GetZtampName());

	return new ApiManager::ApiMappedList(list);
}

QHash<QByteArray, Ztamp *> ZtampManager::listOfZtamps;
