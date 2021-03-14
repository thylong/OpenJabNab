#ifndef _ACCOUNTMANAGER_H_
#define _ACCOUNTMANAGER_H_

#include <QDateTime>
#include <QtSql/QtSql>
#include <QList>
#include <QHash>
#include <QDir>
#include <QSettings>
#include "global.h"
#include "account.h"
#include "bunny.h"
#include "apihandler.h"


typedef struct {
	Account * account;
	unsigned int expire_time;
} TokenData;

class OJN_EXPORT AccountManager : public ApiHandler<AccountManager>
{
	friend class OpenJabNab;
	friend class File2Db;
public:
	static AccountManager & Instance();

	Account const& GetAccount(QByteArray const&);
	static Account* GetAccountByLogin(QByteArray const&);
	static Account const& Guest();
	QByteArray GetToken(QString const& login, QByteArray const& hash);
	QByteArray GetToken(QString const& login);
	void SaveAccounts();

	static QDir * GetUserDir(Bunny *);
	static QDir * GetUserDir(Account *);

	static QStringList ListSoundGroup(QString);
	static QStringList ListSoundsInGroup(QString, QString);

protected:
	static inline void Init();
	static inline void Close();
	virtual ~AccountManager();

private:
	AccountManager();
	void LoadAccounts();
	void LoadAccount(QString);
	static void InitApiCalls();
	QList<Account *> listOfAccounts;
	QHash<QString, Account *> listOfAccountsByName;
	QHash<QByteArray, TokenData> listOfTokens;

	// Files settings
	QVariant GetSettings(QString const& key, QVariant const& defaultValue = QVariant()) const;
	void SetSettings(QString const& key, QVariant const& value);
	void RemoveSettings(QString const& key);
	QSettings * settings;

	// API
	API_CALL(Api_Auth);
	API_CALL(Api_AuthAs);
	API_CALL(Api_ChangePasswd);
	API_CALL(Api_ChangeUsername);
	API_CALL(Api_RegisterNewAccount);
	API_CALL(Api_RemoveAccount);
	API_CALL(Api_ReloadAccount);
	API_CALL(Api_AddBunny);
	API_CALL(Api_RemoveBunny);
    	API_CALL(Api_RemoveZtamp);
	API_CALL(Api_SetToken);
	API_CALL(Api_SetAdmin);
	API_CALL(Api_SetPremium);
	API_CALL(Api_SetVip);
	API_CALL(Api_SetLanguage);
	API_CALL(Api_GetLanguage);
	API_CALL(Api_SetEmail);
	API_CALL(Api_GetEmail);
	API_CALL(Api_GetUserInfos);
	API_CALL(Api_SetUserInfos);
	API_CALL(Api_GetUserlist);
	API_CALL(Api_GetConnectedUsers);
	API_CALL(Api_GetListOfAdmins);
	API_CALL(Api_GetListOfPremiums);
	API_CALL(Api_GetListOfVips);
	API_CALL(Api_SaveAccounts);
	API_CALL(Api_CheckAccounts);
	API_CALL(Api_InactiveAccounts);
	API_CALL(Api_GetUserLogins);
	API_CALL(Api_GetUserAbuses);

	// API FILES
	API_CALL(Api_DelSoundGroup);
	API_CALL(Api_EditSoundGroup);
	API_CALL(Api_ListSoundGroup);
	API_CALL(Api_AddSound);
	API_CALL(Api_RemoveSound);
	API_CALL(Api_ListSound);

	// NEW API
	API_CALL(Api_User);
};

inline void AccountManager::Init()
{
	Instance().LoadAccounts();
	InitApiCalls();
}

inline void AccountManager::Close()
{
	Instance().SaveAccounts();
}

#endif
