#include <QCoreApplication>
#include <QCryptographicHash>
#include <QDateTime>
#include <QLibrary>
#include <QString>
#include <QUuid>
#include <QTimer>
#include <QDebug>
#include "account.h"
#include "accountmanager.h"
#include "apimanager.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "ztamp.h"
#include "bunnymanager.h"
#include "log.h"
#include "httprequest.h"
#include "settings.h"
#include "dbmanager.h"
#include "translator.h"

AccountManager::AccountManager()
{
	QDir dir = QDir(GlobalSettings::GetConfigDir());
	settings = new QSettings(dir.absoluteFilePath("file_groups.ini"), QSettings::IniFormat);
}

AccountManager & AccountManager::Instance()
{
  static AccountManager p;
  return p;
}

AccountManager::~AccountManager()
{
	delete settings;
	foreach(Account * a, listOfAccounts)
		delete a;
}

void AccountManager::LoadAccounts()
{
        QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("SELECT username, settings FROM account");
	query->exec();
	while(query->next())
	{
		QDataStream stream(query->value(1).toByteArray());
		stream.setVersion(QDataStream::Qt_4_3);
		int version;
		stream >> version;
		Account * a = new Account(stream, version);
		if (stream.status() != QDataStream::Ok)
		{
			QDataStream stream2(query->value(1).toByteArray());
			stream2.setVersion(QDataStream::Qt_4_3);
			int version;
			stream2 >> version;
			a = new Account(stream2, version == 1 ? 2 : 1);
			if (stream2.status() != QDataStream::Ok)
			{
				LogWarning(QString("Problem when loading account %1").arg(query->value(0).toString()));
				delete a;
				continue;
			}
		}
		listOfAccounts.append(a);
		listOfAccountsByName.insert(a->GetLogin(), a);
	}
	delete query;
	DbManager::releaseDb();

	if(listOfAccounts.count() == 0)
	{
		LogWarning("No account loaded ... inserting default admin");
		Account * a = new Account(Account::DefaultAdmin);
		listOfAccounts.append(a);
		listOfAccountsByName.insert(a->GetLogin(), a);
	}
	LogInfo(QString("Total of accounts: %1").arg(listOfAccounts.count()));
}

void AccountManager::LoadAccount(QString usr)
{
        QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("SELECT settings FROM account WHERE username=:username");
	query->bindValue(":username", usr);
	query->exec();
	while(query->next())
	{
		Account * _a = GetAccountByLogin(usr.toLatin1());
		if(_a != NULL)
		{
			int indexOfAccount = listOfAccounts.indexOf(_a);
			listOfAccounts.removeAt(indexOfAccount);
			listOfAccountsByName.remove(_a->GetLogin());
		}
		delete _a;

		QDataStream stream(query->value(0).toByteArray());
		stream.setVersion(QDataStream::Qt_4_3);
		int version;
		stream >> version;
		Account * a = new Account(stream, version);
		if (stream.status() != QDataStream::Ok)
		{
			QDataStream stream2(query->value(0).toByteArray());
			stream2.setVersion(QDataStream::Qt_4_3);
			int version;
			stream2 >> version;
			a = new Account(stream2, version == 1 ? 2 : 1);
			if (stream2.status() != QDataStream::Ok)
			{
				LogWarning(QString("Problem when loading account %1").arg(query->value(0).toString()));
				delete a;
				continue;
			}
		}
		listOfAccounts.append(a);
		listOfAccountsByName.insert(a->GetLogin(), a);
	}
	delete query;
	DbManager::releaseDb();
}

void AccountManager::SaveAccounts()
{
        QSqlDatabase db = DbManager::getOpenDb();
	foreach(Account * a, listOfAccounts)
	{
		if(a->GetLogin() != "admin" && a->SaveNeeded())
		{
			QSqlQuery *query = new QSqlQuery(db);
			QByteArray byteArray;
			QDataStream stream(&byteArray, QIODevice::WriteOnly);
			stream.setVersion(QDataStream::Qt_4_3);
			stream << Account::Version();
			stream << *a;
			query->prepare("INSERT INTO account SET `username`=:user, `settings`=:settings ON DUPLICATE KEY UPDATE `settings`=:settings2");
			query->bindValue(":user", a->GetLogin());
			query->bindValue(":settings", byteArray);
			query->bindValue(":settings2", byteArray);
			bool ret = query->exec();
			if(ret)
			{
				a->SetSaveNeeded(false);
			}
			else
			{
				LogError(QString("Impossible to insert account in DB : %1").arg(query->lastError().driverText()));
				continue;
			}
			delete query;
		}
	}
	DbManager::releaseDb();
}

Account const& AccountManager::Guest()
{
	static Account guest(Account::Guest);
	return guest;
}

Account const& AccountManager::GetAccount(QByteArray const& token)
{
	QHash<QByteArray, TokenData>::iterator it = listOfTokens.find(token);
	if(it != listOfTokens.end())
	{
		unsigned int now = QDateTime::currentDateTime().toTime_t();
		if(now < it->expire_time)
		{
			it->expire_time = now + GlobalSettings::GetInt("Config/SessionTimeout", 300); // default : 5min

			//listOfTokens.insert(token, t);
			return *(it->account);
		}
		else
		{
			listOfTokens.erase(it);
			return Guest();
		}
	}
	return Guest();
}

Account * AccountManager::GetAccountByLogin(QByteArray const& login)
{
	if(Instance().listOfAccountsByName.contains(login))
		return Instance().listOfAccountsByName.value(login);
	return NULL;
}

QByteArray AccountManager::GetToken(QString const& login)
{
	QHash<QString, Account *>::const_iterator it = listOfAccountsByName.find(login);
	if(it != listOfAccountsByName.end())
	{
		QByteArray token = QCryptographicHash::hash(QUuid::createUuid().toString().toLatin1(), QCryptographicHash::Md5).toHex();
		TokenData t;
		t.account = *it;
		t.expire_time = QDateTime::currentDateTime().toTime_t() + GlobalSettings::GetInt("Config/SessionTimeout", 300);
		(*it)->SetToken(token);
		listOfTokens.insert(token, t);
		return token;
	}
	LogError(QString("Bad login : user=%1").arg(QString(login)));
	return QByteArray();
}

QByteArray AccountManager::GetToken(QString const& login, QByteArray const& hash)
{
	QHash<QString, Account *>::const_iterator it = listOfAccountsByName.find(login);
	if(it != listOfAccountsByName.end())
	{
		if((*it)->GetPasswordHash() == hash)
		{
			// Generate random token
			QByteArray token = QCryptographicHash::hash(QUuid::createUuid().toString().toLatin1(), QCryptographicHash::Md5).toHex();
			TokenData t;
			t.account = *it;
			t.expire_time = QDateTime::currentDateTime().toTime_t() + GlobalSettings::GetInt("Config/SessionTimeout", 300);
			(*it)->SetToken(token);
			listOfTokens.insert(token, t);
			return token;
		}
		LogError(QString("Bad login : user=%1, hash=%2, proposed hash=%3").arg(login,QString((*it)->GetPasswordHash().toHex()),QString(hash.toHex())));
		return QByteArray();
	}
	LogError(QString("Bad login : user=%1").arg(QString(login)));
	return QByteArray();
}

// Settings
inline QVariant AccountManager::GetSettings(QString const& key, QVariant const& defaultValue) const
{
	return settings->value(key, defaultValue);
}

inline void AccountManager::SetSettings(QString const& key, QVariant const& value)
{
	settings->setValue(key, value);
	settings->sync();
}

inline void AccountManager::RemoveSettings(QString const& key)
{
	settings->remove(key);
	settings->sync();
}

QDir * AccountManager::GetUserDir(Account * acc)
{
	QString accountName = QCryptographicHash::hash(acc->GetUsername().toLatin1(), QCryptographicHash::Md5).toHex();
	QDir userDir(GlobalSettings::GetString("Config/RealHttpRoot"));
	if (!userDir.cd("users"))
	{
		if (!userDir.mkdir("users"))
		{
			LogError(QString("Unable to create users directory !\n"));
		}
		userDir.cd("users");
	}
	if (!userDir.cd(accountName))
	{
		if (!userDir.mkdir(accountName))
		{
			LogError(QString("Unable to create %1 directory !").arg(accountName));
		}
		userDir.cd(accountName);
	}
	QStringList filters;
	filters << "*.mp3";
	userDir.setNameFilters(filters);
	return new QDir(userDir);
}

QDir * AccountManager::GetUserDir(Bunny * b)
{
	QString accountName = QCryptographicHash::hash(b->GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QDir userDir(GlobalSettings::GetString("Config/RealHttpRoot"));
	if (!userDir.cd("users"))
	{
		if (!userDir.mkdir("users"))
		{
			LogError(QString("Unable to create users directory !"));
		}
		userDir.cd("users");
	}
	if (!userDir.cd(accountName))
	{
		if (!userDir.mkdir(accountName))
		{
			LogError(QString("Unable to create %1 directory !").arg(accountName));
		}
		userDir.cd(accountName);
	}
	QStringList filters;
	filters << "*.mp3";
	userDir.setNameFilters(filters);
	return new QDir(userDir);
}

QStringList AccountManager::ListSoundGroup(QString login)
{
	return Instance().GetSettings("Groups/" + login, QStringList()).toStringList();
}

QStringList AccountManager::ListSoundsInGroup(QString login, QString group)
{
	return Instance().GetSettings(login + "_" + group + "/Files", QStringList()).toStringList();
}

/*******
 * API *
 *******/

void AccountManager::InitApiCalls()
{
	DECLARE_API_CALL("auth(login,pass)", &AccountManager::Api_Auth);
	DECLARE_API_CALL("authAs(login)", &AccountManager::Api_AuthAs);
	DECLARE_API_CALL("changePassword(login,pass)", &AccountManager::Api_ChangePasswd);
	DECLARE_API_CALL("changeUsername(login,username)", &AccountManager::Api_ChangeUsername);
	DECLARE_API_CALL("registerNewAccount(login,username,pass)", &AccountManager::Api_RegisterNewAccount);
	DECLARE_API_CALL("removeAccount(login)", &AccountManager::Api_RemoveAccount);
	DECLARE_API_CALL("reloadAccount(login)", &AccountManager::Api_ReloadAccount);
	DECLARE_API_CALL("addBunny(login,bunnyid)", &AccountManager::Api_AddBunny);
	DECLARE_API_CALL("removeBunny(login,bunnyid)", &AccountManager::Api_RemoveBunny);
	DECLARE_API_CALL("removeZtamp(login,zid)", &AccountManager::Api_RemoveZtamp);
	DECLARE_API_CALL("settoken(tk)", &AccountManager::Api_SetToken);
	DECLARE_API_CALL("setadmin(user)", &AccountManager::Api_SetAdmin);
	DECLARE_API_CALL("setpremium(user)", &AccountManager::Api_SetPremium);
	DECLARE_API_CALL("setvip(user)", &AccountManager::Api_SetVip);
	DECLARE_API_CALL("setlanguage(login,lng)", &AccountManager::Api_SetLanguage);
	DECLARE_API_CALL("getlanguage(login)", &AccountManager::Api_GetLanguage);
	DECLARE_API_CALL("setemail(login,email)", &AccountManager::Api_SetEmail);
	DECLARE_API_CALL("getemail(login)", &AccountManager::Api_GetEmail);
	DECLARE_API_CALL("infos(user)", &AccountManager::Api_GetUserInfos);
	DECLARE_API_CALL("setinfo(user,setting,value)", &AccountManager::Api_SetUserInfos);
	DECLARE_API_CALL("GetUserlist()", &AccountManager::Api_GetUserlist);
	DECLARE_API_CALL("GetConnectedUsers()", &AccountManager::Api_GetConnectedUsers);
	DECLARE_API_CALL("GetListOfAdmins()", &AccountManager::Api_GetListOfAdmins);
	DECLARE_API_CALL("GetListOfPremiums()", &AccountManager::Api_GetListOfPremiums);
	DECLARE_API_CALL("GetListOfVips()", &AccountManager::Api_GetListOfVips);
	DECLARE_API_CALL("GetUserLogins()", &AccountManager::Api_GetUserLogins);
	DECLARE_API_CALL("GetUserAbuses()", &AccountManager::Api_GetUserAbuses);
	DECLARE_API_CALL("saveAccounts()", &AccountManager::Api_SaveAccounts);
	DECLARE_API_CALL("checkAccounts()", &AccountManager::Api_CheckAccounts);
	DECLARE_API_CALL("inactiveAccounts()", &AccountManager::Api_InactiveAccounts);

	DECLARE_API_CALL("delgroup(login,group)", &AccountManager::Api_DelSoundGroup);
	DECLARE_API_CALL("editgroup(login,group)", &AccountManager::Api_EditSoundGroup);
	DECLARE_API_CALL("listgroup(login)", &AccountManager::Api_ListSoundGroup);
	DECLARE_API_CALL("listsound(login,group)", &AccountManager::Api_ListSound);
	DECLARE_API_CALL("addsound(login,group,sound)", &AccountManager::Api_AddSound);
	DECLARE_API_CALL("removesound(login,group,sound)", &AccountManager::Api_RemoveSound);

	DECLARE_API_CALL("user()", &AccountManager::Api_User);
}

API_CALL(AccountManager::Api_Auth)
{
	Q_UNUSED(account);

	QString login = hRequest.GetArg("login");
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(!ac)
	{
		return new ApiManager::ApiError(Translator::tr("Login not found"));
	}
	if(!ac->IsAdmin() && !ac->IsPremium() && !ac->IsVip() && ac->GetLoginCount() > GlobalSettings::GetInt("User/MaxLogin", 255))
		return new ApiManager::ApiError(Translator::tr("Sorry, too many logins today (limit is %1)").arg(QString::number(GlobalSettings::GetInt("User/MaxLogin", 255))));
	QByteArray retour = GetToken(login, QCryptographicHash::hash(hRequest.GetArg("pass").toLatin1(), QCryptographicHash::Md5));
	if(retour == QByteArray())
		return new ApiManager::ApiError(Translator::tr("Access denied"));

	LogInfo(QString("User login : %1").arg(login));
  QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("UPDATE account SET lastlogin=NOW() WHERE username=:username;");
	query->bindValue(":username", ac->GetLogin());
	query->exec();
	delete query;
	DbManager::releaseDb();

	if(!hRequest.HasArg("notcount"))
		ac->AddLoginCount();
	return new ApiManager::ApiString(retour);
}

API_CALL(AccountManager::Api_AuthAs)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString login = hRequest.GetArg("login");
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(!ac)
	{
		return new ApiManager::ApiError(Translator::tr("Login not found"));
	}
	QByteArray retour = GetToken(login);
	if(retour == QByteArray())
		return new ApiManager::ApiError(Translator::tr("Access denied"));

	LogInfo(QString("User %2 logged as : %1").arg(login, account.GetUsername()));

	return new ApiManager::ApiString(retour);
}

API_CALL(AccountManager::Api_ChangePasswd)
{
	QString login = hRequest.GetArg("login");
	QString pwd = hRequest.GetArg("pass");
	LogWarning(QString("Login: %1 Pwd: %2 user %3").arg(login,pwd,account.GetLogin()));
	if(login == "" || pwd == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	ac->SetPassword(QCryptographicHash::hash(pwd.toLatin1(), QCryptographicHash::Md5));
	LogInfo(Translator::tr("Password changed for user '%1'", account).arg(login));
	return new ApiManager::ApiOk(Translator::tr("Password changed", account));
}

API_CALL(AccountManager::Api_ChangeUsername)
{
        QString login = hRequest.GetArg("login");
        QString username = hRequest.GetArg("username");
        if(login == "" || username == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

        Account *ac = listOfAccountsByName.value(login.toLatin1());
        if(ac == NULL)
                return new ApiManager::ApiError(Translator::tr("Login not found", account));

        ac->SetUsername(username);
        LogInfo(QString("Username changed for user '%1'").arg(login));
        return new ApiManager::ApiOk(Translator::tr("Username changed", account));
}

API_CALL(AccountManager::Api_RegisterNewAccount)
{
	if(GlobalSettings::Get("Config/AllowAnonymousRegistration", false) == false && !account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString login = hRequest.GetArg("login");
	if(listOfAccountsByName.contains(login))
		return new ApiManager::ApiError(Translator::tr("Account '%1' already exists", account).arg(hRequest.GetArg("login")));

	Account * a = new Account(login, hRequest.GetArg("username"), QCryptographicHash::hash(hRequest.GetArg("pass").toLatin1(), QCryptographicHash::Md5));
	listOfAccounts.append(a);
	listOfAccountsByName.insert(a->GetLogin(), a);
	if(listOfAccounts.count() == 2 && listOfAccountsByName.contains("admin")) {
		LogWarning("Registering first account, set him admin");
		a->setAdmin();
		//Todo: Drop default admin right now, security issues
	}
	SaveAccounts();
	return new ApiManager::ApiOk(Translator::tr("New account created : %1", account).arg(hRequest.GetArg("login")));
}

API_CALL(AccountManager::Api_RemoveAccount)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString login = hRequest.GetArg("login");
	if(!listOfAccountsByName.contains(login))
		return new ApiManager::ApiError(Translator::tr("Account '%1' doesn't exist", account).arg(login));

	Account * a = GetAccountByLogin(login.toLatin1());
	int indexOfAccount = listOfAccounts.indexOf(a);
	listOfAccounts.removeAt(indexOfAccount);
	listOfAccountsByName.remove(a->GetLogin());

        QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("DELETE FROM account WHERE username=:username;");
	query->bindValue(":username", a->GetLogin());
	query->exec();
	if(query->numRowsAffected () > 0)
	{
		return new ApiManager::ApiOk(Translator::tr("Account %1 removed", account).arg(login));
		delete query;
		DbManager::releaseDb();
	}
	delete query;
	return new ApiManager::ApiError(Translator::tr("Error when removing account %1", account).arg(login));
}

API_CALL(AccountManager::Api_SaveAccounts)
{
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	SaveAccounts();

	return new ApiManager::ApiOk(Translator::tr("Accounts saved", account));
}

API_CALL(AccountManager::Api_CheckAccounts)
{
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	foreach (Account* a, listOfAccounts)
	{
		QList<QByteArray> bunnies = a->GetBunniesList();
		foreach(QByteArray bunnyid, bunnies)
		{
			Bunny *b = BunnyManager::GetBunny(bunnyid);
			QString owner = b->GetGlobalSetting("OwnerAccount", QString()).toString();
			if(owner != a->GetLogin())
			{
				LogInfo(QString("Remove bunny '%1' from '%2' account. %3").arg(QString(bunnyid), a->GetLogin(), owner == QString() ? "Bunny is free" : QString("Real owner is '%1'").arg(owner)));
				a->RemoveBunny(bunnyid);
			}
		}
	}
	return new ApiManager::ApiOk(Translator::tr("Accounts checked and cleaned", account));
}

API_CALL(AccountManager::Api_InactiveAccounts)
{
	Q_UNUSED(hRequest);

	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach (Account* a, listOfAccounts)
	{
		if(a->GetBunniesList().length() == 0)
		{
			list.insert(a->GetLogin(), a->GetLastLogin().toString("yyyy-MM-dd hh:mm:ss"));
		}
	}

	return new ApiManager::ApiMappedList(list);
}

API_CALL(AccountManager::Api_ReloadAccount)
{
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString login = hRequest.GetArg("login");
	//if(!listOfAccountsByName.contains(login))
	//	return new ApiManager::ApiError(QString("Account '%1' does not exist").arg(login));

	LoadAccount(login);
	return new ApiManager::ApiOk(Translator::tr("Account %1 reloaded", account).arg(login));
}

API_CALL(AccountManager::Api_AddBunny)
{
	// Only admins can add a bunny to an account
	if(GlobalSettings::Get("Config/AllowUserManageBunny", false) == false && !account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString login = hRequest.GetArg("login");
	if(!account.IsAdmin() && login != account.GetLogin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!listOfAccountsByName.contains(login))
		return new ApiManager::ApiError(Translator::tr("Account '%1' doesn't exist", account).arg(login));
	QString bunnyid = hRequest.GetArg("bunnyid");

	// Lock bunny to this account
	Bunny *b = BunnyManager::GetBunny(bunnyid.toLatin1());
	QString own = b->GetGlobalSetting("OwnerAccount","").toString();
	if(own != "" && own != login)
		return new ApiManager::ApiError(Translator::tr("Bunny %1 is already attached to this account: '%2'", account).arg(bunnyid,own));

	b->SetGlobalSetting("OwnerAccount", login);
	QByteArray id = listOfAccountsByName.value(login)->AddBunny(bunnyid.toLatin1());
	listOfAccountsByName.value(login)->SetSaveNeeded(true);
	return new ApiManager::ApiOk(Translator::tr("Bunny '%1' added to account '%2'", account).arg(QString(id)).arg(login));
}

API_CALL(AccountManager::Api_RemoveBunny)
{
	// Only admin can remove bunny to any accounts, else an auth user can remove a bunny from his account
	QString login = hRequest.GetArg("login");
	/* Account doesn't exist */
	if(!listOfAccountsByName.contains(login))
		return new ApiManager::ApiError(Translator::tr("Account '%1' doesn't exist", account).arg(login));
	/* user is not admin and (is not allowed or it's not his account) */
	else if(!account.IsAdmin() && (GlobalSettings::Get("Config/AllowUserManageBunny", false) != true || account.GetLogin() != login))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString bunnyID = hRequest.GetArg("bunnyid");
	if(listOfAccountsByName.value(login)->RemoveBunny(bunnyID.toLatin1())) {
		Bunny *b = BunnyManager::GetBunny(bunnyID.toLatin1());
		b->RemoveGlobalSetting("OwnerAccount");
		listOfAccountsByName.value(login)->SetSaveNeeded(true);
		return new ApiManager::ApiOk(Translator::tr("Bunny '%1' removed from account '%2'", account).arg(bunnyID).arg(login));
	} else
		return new ApiManager::ApiError(Translator::tr("Can't remove bunny '%1' from account '%2'", account).arg(bunnyID).arg(login));
}

API_CALL(AccountManager::Api_RemoveZtamp)
{
	// Only admin can remove ztamp to any accounts, else an auth user can remove a ztamp from his account
	QString login = hRequest.GetArg("login");
	/* Account doesn't exist */
	if(!listOfAccountsByName.contains(login))
		return new ApiManager::ApiError(Translator::tr("Account '%1' doesn't exist", account).arg(login));
	/* user is not admin and (is not allowed or it's not his account) */
	//else if(!account.IsAdmin() && (GlobalSettings::Get("Config/AllowUserManageZtamp", false) != true || account.GetLogin() != login))
	else if( !(account.IsAdmin() || account.GetLogin() == login) )
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QString zID = hRequest.GetArg("zid");
	if(listOfAccountsByName.value(login)->RemoveZtamp(zID.toLatin1()))
	{
		Ztamp *z = ZtampManager::GetZtamp(zID.toLatin1());
		QStringList list = z->GetGlobalSetting("OwnerAccounts", QStringList()).toStringList();
		list.removeAll(login);
		z->SetGlobalSetting("OwnerAccounts", list);
		listOfAccountsByName.value(login)->SetSaveNeeded(true);
		return new ApiManager::ApiOk(Translator::tr("Ztamp '%1' removed from account '%2'", account).arg(zID).arg(login));
	} else
		return new ApiManager::ApiError(Translator::tr("Can't remove ztamp '%1' from account '%2'", account).arg(zID).arg(login));
}

API_CALL(AccountManager::Api_SetToken)
{
	QHash<QString, Account *>::iterator it = listOfAccountsByName.find(account.GetLogin());
	if(it != listOfAccountsByName.end())
	{
		it.value()->SetToken(hRequest.GetArg("tk").toLatin1());
		return new ApiManager::ApiString(Translator::tr("Token changed", account));
	}

	//LogError("Account not found");
	return new ApiManager::ApiError(Translator::tr("Access denied", account));
}

API_CALL(AccountManager::Api_SetUserInfos)
{
	QString login = hRequest.GetArg("user");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	QString setting = hRequest.GetArg("setting");
	int value = hRequest.GetArg("value").toInt();
	if(setting == "login_count") {
		ac->SetLoginCount(value);
	} else if(setting == "abuse") {
		ac->SetAbuse();					// Update abuse date
		ac->SetAbuseCount(value);		// Clear abuse count
	} 

	QMap<QString, QVariant> list;
	list.insert("login",ac->GetLogin());
	list.insert("username",ac->GetUsername());
	list.insert("language",ac->GetLanguage());
	list.insert("email",ac->GetEmail());
	list.insert("isValid",listOfTokens.contains(ac->GetToken()));
	list.insert("token",QString(ac->GetToken()));
	list.insert("isAdmin",ac->IsAdmin());
	list.insert("isPremium",ac->IsPremium());
	list.insert("isVip",ac->IsVip());
	list.insert("loginCount",ac->GetLoginCount());
	list.insert("lastLogin",ac->GetLastLogin().toString("yyyy-MM-dd hh:mm:ss"));
	list.insert("abuseCount",ac->GetAbuseCount());
	list.insert("lastBanStart",ac->GetLastBanStart().toString("yyyy-MM-dd hh:mm:ss"));
	list.insert("lastBanEnd",ac->GetLastBanEnd().toString("yyyy-MM-dd hh:mm:ss"));
	return new ApiManager::ApiMappedList(list);
}

API_CALL(AccountManager::Api_GetUserInfos)
{
	QString login = hRequest.GetArg("user");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	QMap<QString, QVariant> list;
	list.insert("login",ac->GetLogin());
	list.insert("username",ac->GetUsername());
	list.insert("language",ac->GetLanguage());
	list.insert("email",ac->GetEmail());
	list.insert("isValid",listOfTokens.contains(ac->GetToken()));
	list.insert("token",QString(ac->GetToken()));
	list.insert("isAdmin",ac->IsAdmin());
	list.insert("isPremium",ac->IsPremium());
	list.insert("isVip",ac->IsVip());
	list.insert("loginCount",ac->GetLoginCount());
	list.insert("lastLogin",ac->GetLastLogin().toString("yyyy-MM-dd hh:mm:ss"));
	list.insert("abuseCount",ac->GetAbuseCount());
	list.insert("lastBanStart",ac->GetLastBanStart().toString("yyyy-MM-dd hh:mm:ss"));
	list.insert("lastBanEnd",ac->GetLastBanEnd().toString("yyyy-MM-dd hh:mm:ss"));
	return new ApiManager::ApiMappedList(list);
}

API_CALL(AccountManager::Api_GetUserAbuses)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach (Account* a, listOfAccounts)
		list.insert(a->GetLogin(),QString::number(a->GetAbuseCount()) + ";" + a->GetLastBanStart().toString("yyyy-MM-dd hh:mm:ss") + ";" + a->GetLastBanEnd().toString("yyyy-MM-dd hh:mm:ss"));

	return new ApiManager::ApiMappedList(list);
}

API_CALL(AccountManager::Api_GetUserLogins)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach (Account* a, listOfAccounts)
		list.insert(a->GetLogin(),a->GetLoginCount());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(AccountManager::Api_GetUserlist)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QMap<QString, QVariant> list;
	foreach (Account* a, listOfAccounts)
		list.insert(a->GetLogin(),a->GetUsername());

	return new ApiManager::ApiMappedList(list);
}

API_CALL(AccountManager::Api_GetConnectedUsers)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (Account* a, listOfAccounts)
		if(listOfTokens.contains(a->GetToken()))
			list.append(a->GetLogin());
	return new ApiManager::ApiList(list);
}

API_CALL(AccountManager::Api_GetListOfAdmins)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (Account* a, listOfAccounts)
		if(a->IsAdmin())
			list.append(a->GetLogin());
	return new ApiManager::ApiList(list);
}

API_CALL(AccountManager::Api_GetListOfPremiums)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (Account* a, listOfAccounts)
		if(a->IsPremium())
			list.append(a->GetLogin());
	return new ApiManager::ApiList(list);
}

API_CALL(AccountManager::Api_GetListOfVips)
{
	Q_UNUSED(hRequest);
	if(!account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	QList<QString> list;
	foreach (Account* a, listOfAccounts)
		if(a->IsVip())
			list.append(a->GetLogin());
	return new ApiManager::ApiList(list);
}

API_CALL(AccountManager::Api_SetAdmin)
{
	QString login = hRequest.GetArg("user");
	QString adm = hRequest.GetArg("adm");

	if(login == "" || !account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	/* Get User */
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));
	if(adm == "user")
		ac->setAdmin(false);
	else
		ac->setAdmin();

	return new ApiManager::ApiOk(Translator::tr("user '%1' is now admin", account).arg(login));
}

API_CALL(AccountManager::Api_SetPremium)
{
	QString login = hRequest.GetArg("user");
	QString premium = hRequest.GetArg("premium");

	if(login == "" || !account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	/* Get User */
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));
	if(premium == "false")
		ac->setPremium(false);
	else
		ac->setPremium(true);

	return new ApiManager::ApiOk(Translator::tr("user '%1' is now premium", account).arg(login));
}

API_CALL(AccountManager::Api_SetVip)
{
	QString login = hRequest.GetArg("user");
	QString vip = hRequest.GetArg("vip");

	if(login == "" || !account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	/* Get User */
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));
	if(vip == "false")
		ac->setVip(false);
	else
		ac->setVip(true);

	return new ApiManager::ApiOk(Translator::tr("user '%1' is now VIP", account).arg(login));
}

API_CALL(AccountManager::Api_SetLanguage)
{
	Q_UNUSED(account);

	QString login = hRequest.GetArg("login");
	QString language = hRequest.GetArg("lng");

	if(login == "")
		return new ApiManager::ApiError(Translator::tr("No account specified", account));

	/* Get User */
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Account not found"));
	ac->SetLanguage(language);
	return new ApiManager::ApiOk(Translator::tr("Language is now '%1' for user '%2'", account).arg(language, login));
}

API_CALL(AccountManager::Api_GetLanguage)
{
	Q_UNUSED(account);

	QString login = hRequest.GetArg("login");

	if(login == "")
		return new ApiManager::ApiError(Translator::tr("No account specified", account));

	/* Get User */
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Account not found", account));
	return new ApiManager::ApiString(ac->GetLanguage());
}

API_CALL(AccountManager::Api_SetEmail)
{
	QString login = hRequest.GetArg("login");
	QString email = hRequest.GetArg("email");

	if(login == "")
		return new ApiManager::ApiError(Translator::tr("No account specified", account));

	/* Get User */
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Account not found", account));
	ac->SetEmail(email);
	return new ApiManager::ApiOk(Translator::tr("Email is now '%1' for user '%2'", account).arg(email, login));
}

API_CALL(AccountManager::Api_GetEmail)
{
	QString login = hRequest.GetArg("login");

	if(login == "")
		return new ApiManager::ApiError(Translator::tr("No account specified", account));

	/* Get User */
	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Account not found", account));
	return new ApiManager::ApiString(ac->GetEmail());
}

API_CALL(AccountManager::Api_EditSoundGroup)
{
	QString login = hRequest.GetArg("login");
	QString group = hRequest.GetArg("group");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	if(hRequest.HasArg("create") && hRequest.GetArg("create") == group)
	{
		QStringList list = GetSettings("Groups/" + login, QStringList()).toStringList();
		if(list.contains(group))
		{
			return new ApiManager::ApiError(Translator::tr("Group already exists", account));
		}
		list << group;
		SetSettings("Groups/" + login, list);
		SetSettings(login + "_" + group + "/Files", QStringList());
		SetSettings(login + "_" + group + "/Private", true);
		return new ApiManager::ApiOk(Translator::tr("Group added successfully", account));
	}
	else if(hRequest.HasArg("rename") && hRequest.GetArg("rename") != "")
	{
	}

	if(hRequest.HasArg("private") && hRequest.GetArg("private") != "")
	{
	}
	return new ApiManager::ApiError(Translator::tr("No action specified", account));
}

API_CALL(AccountManager::Api_DelSoundGroup)
{
	QString login = hRequest.GetArg("login");
	QString group = hRequest.GetArg("group");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	QStringList list = GetSettings("Groups/" + login, QStringList()).toStringList();
	if(!list.contains(group))
	{
		return new ApiManager::ApiError(Translator::tr("Group doesn't exist", account));
	}
	list.removeAll(group);
	SetSettings("Groups/" + login, list);
	RemoveSettings(login + "_" + group);
	return new ApiManager::ApiOk(Translator::tr("Group removed successfully", account));
}

API_CALL(AccountManager::Api_ListSoundGroup)
{
	QString login = hRequest.GetArg("login");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	return new ApiManager::ApiList(GetSettings("Groups/" + login, QStringList()).toStringList());
}

API_CALL(AccountManager::Api_AddSound)
{
	QString login = hRequest.GetArg("login");
	QString group = hRequest.GetArg("group");
	QString sound = hRequest.GetArg("sound");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	QStringList files = GetSettings(login + "_" + group + "/Files", QStringList()).toStringList();
	if(files.contains(sound))
		return new ApiManager::ApiError(Translator::tr("This file is already in the group", account));

	files << sound;
	SetSettings(login + "_" + group + "/Files", files);
	return new ApiManager::ApiOk(Translator::tr("File added", account));
}

API_CALL(AccountManager::Api_RemoveSound)
{
	QString login = hRequest.GetArg("login");
	QString group = hRequest.GetArg("group");
	QString sound = hRequest.GetArg("sound");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	QStringList files = GetSettings(login + "_" + group + "/Files", QStringList()).toStringList();
	if(!files.contains(sound))
		return new ApiManager::ApiError(Translator::tr("This file is not in the group", account));

	files.removeAll(sound);
	SetSettings(login + "_" + group + "/Files", files);
	return new ApiManager::ApiOk(Translator::tr("File removed", account));
}

API_CALL(AccountManager::Api_ListSound)
{
	QString login = hRequest.GetArg("login");
	QString group = hRequest.GetArg("group");
	if(login == "" || (!account.IsAdmin() && login != account.GetLogin()))
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	Account *ac = listOfAccountsByName.value(login.toLatin1());
	if(ac == NULL)
		return new ApiManager::ApiError(Translator::tr("Login not found", account));

	return new ApiManager::ApiList(GetSettings(login + "_" + group + "/Files", QStringList()).toStringList());
}

API_CALL(AccountManager::Api_User)
{
	QString login = account.GetLogin();

	if(hRequest.HasArg("login"))
		login = hRequest.GetArg("login");

	Account *user = listOfAccountsByName.value(login.toLatin1());
	if(user == NULL)
		return new ApiManager::ApiError(Translator::tr("Account '%1' not found", account).arg(login));

	//if(login != "" && (!account.IsAdmin() || login != account.GetLogin()))
	if(user != &account && !account.IsAdmin())
		return new ApiManager::ApiError(Translator::tr("Access denied", account));

	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "set")
	{
		if(hRequest.HasArg("email"))
		{
			QString email = hRequest.GetArg("email");
			user->SetEmail(email);
		}
		if(hRequest.HasArg("username"))
		{
			QString username = hRequest.GetArg("username");
			user->SetUsername(username);
		}
		return new ApiManager::ApiString(Translator::tr("User informations updated", account));
	}
	else if(action == "add")
	{
		if(hRequest.HasArg("bunny"))
		{
			QString mac = hRequest.GetArg("bunny");
			user->AddBunny(mac.toLatin1());
			// FIXME Add user as bunny owner in bunny config (see APICall Api_AddBunny)
		}
		else if(hRequest.HasArg("ztamp"))
		{
			QString zID = hRequest.GetArg("ztamp");
			user->AddZtamp(zID.toLatin1());
			// FIXME Add user as ztamp owner in ztamp config (see APICall Api_AddZtamp)
		}
		else
			return new ApiManager::ApiError(Translator::tr("Invalid argument for action '%1'", account).arg(action));
	}
	else
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1'", account).arg("action"));

	user->SetSaveNeeded(true);
	return new ApiManager::ApiString(Translator::tr("User informations updated", account));
}


