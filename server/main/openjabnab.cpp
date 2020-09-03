#include <QTcpSocket>
#include <QString>
#include <QDebug>
#include <QtSql/QtSql>
#include <QTimer>

#include "QsLog.h"
#include "openjabnab.h"
#include "cron.h"
#include "sentencemanager.h"
#include "accountmanager.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "nabaztagmanager.h"
#include "ztamp.h"
#include "ztampmanager.h"
#include "httphandler.h"
#include "log.h"
//#include "netdump.h"
#include "pluginmanager.h"
#include "settings.h"
#include "ttsmanager.h"
#include "xmpphandler.h"
#include "translator.h"
#include "browsercache.h"

#include <QDebug>
#include "dbmanager.h"

#include <QCommandLineParser>

OpenJabNab::OpenJabNab(int argc, char ** argv)
: QCoreApplication(argc, argv)
{
  QCommandLineParser cmd;
  cmd.setApplicationDescription("openJabNab");
  cmd.addHelpOption();
  //parser.addVersionOption();
  cmd.addOptions({
    { {"c","config-dir"},
      QCoreApplication::translate("main","Configuration directory where openjabnab.ini is located"),
      QCoreApplication::translate("main","config-dir"),
      QCoreApplication::applicationDirPath()
    },
  });
  cmd.process(*this);
  auto cfgDir = cmd.value("config-dir");
	GlobalSettings::Init(cfgDir);

  QString logPath = GlobalSettings::GetString("Directories/LogsDir",QCoreApplication::applicationDirPath().append("/logs"));
	QsLogging::Logger::Init(logPath);

	LogInfo("-- OpenJabNab Start --");

	SentenceManager::Init();
	DbManager::Init();
	insertServerInDb();

	Translator::Init();
	TTSManager::Init();
	Cron::Init();
	BunnyManager::Init();
	NabaztagManager::Init();
	Bunny::Init();
	ZtampManager::Init();
	Ztamp::Init();
	AccountManager::Init();
	//NetworkDump::Init();
	BrowserCache::Init(this);
	PluginManager::Init();
	BunnyManager::LoadBunnies();
	ZtampManager::LoadZtamps();

	if(GlobalSettings::Get("Config/HttpListener", true) == true)
	{
		// Create Listeners
		httpListener = new QTcpServer(this);
		httpListener->listen(QHostAddress::LocalHost, GlobalSettings::GetInt("OpenJabNabServers/ListeningHttpPort", 8080));
		connect(httpListener, SIGNAL(newConnection()), this, SLOT(NewHTTPConnection()));
	}
	else
		LogWarning("Warning : HTTP Listener is disabled !");

	if(GlobalSettings::Get("Config/XmppListener", true) == true)
	{
		int port = GlobalSettings::GetInt("OpenJabNabServers/ListeningXmppPort", 5222);
		LogInfo(QString("XMPP Port is: %1").arg(port));
		xmppListener = new QTcpServer(this);
		xmppListener->listen(QHostAddress::Any, port);
		connect(xmppListener, SIGNAL(newConnection()), this, SLOT(NewXMPPConnection()));
	}
	else
		LogWarning("Warning : XMPP Listener is disabled !");

	httpApi = GlobalSettings::Get("Config/HttpApi", true).toBool();
	httpVioletApi = GlobalSettings::Get("Config/HttpVioletApi", true).toBool();
	LogInfo(QString("Parsing of HTTP Api is ").append((httpApi == true)?"enabled":"disabled"));

	autoSaveTmr.setInterval(5 * 60 * 1000);	// 5min
	QObject::connect(&autoSaveTmr,&QTimer::timeout, [&](void)
	{
		AccountManager::Instance().SaveAccounts();
		BunnyManager::SaveBunnies();
		ZtampManager::SaveZtamps();
	});


	nabStatusTmr.setInterval(60 * 1000);	// 1min
	QObject::connect(&nabStatusTmr,&QTimer::timeout, [&](void)
	{
		NabaztagManager::Instance().UpdateStatus();
	});

	autoSaveTmr.start();
	nabStatusTmr.start();
}

void OpenJabNab::insertServerInDb()
{
	QSqlDatabase db = DbManager::getOpenDb();
	QSqlQuery *query = new QSqlQuery(db);
	query->prepare("SELECT * from `server` WHERE `hostname` = :host");
	query->bindValue(":host", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
	query->exec();
	int size = query->size();
	query->finish();
	if(size < 1)
	{
		query->prepare("INSERT INTO `server` SET `hostname`=:host");
		query->bindValue(":host", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
		bool ins = query->exec();
		if(!ins)
		{
			LogError(QString("Impossible to insert value in table 'server' : %1").arg(query->lastError().driverText()));
			Close();
		}
		query->prepare("SELECT * from `server` WHERE `hostname` = :host");
		query->bindValue(":host", GlobalSettings::GetString("OpenJabNabServers/PingServer"));
		query->exec();
	}
	int serverId = query->value(1).toInt();
	GlobalSettings::Set("Database/ServerId", serverId);
	delete query;
	DbManager::releaseDb();
}

void OpenJabNab::Close()
{
	emit Quit();
}

OpenJabNab::~OpenJabNab()
{
	SentenceManager::Close();
	if(xmppListener)
	{
		xmppListener->close();
	}
	if(httpListener)
	{
		httpListener->close();
	}
	BrowserCache::Close();
	//NetworkDump::Close();
	ZtampManager::Close();
	NabaztagManager::Close();
	BunnyManager::Close();
	TTSManager::Close();
	PluginManager::Close();
	AccountManager::Close();
	GlobalSettings::Close();
	DbManager::Close();
	QsLogging::Logger::destroyInstance();

	LogInfo("-- OpenJabNab Close --");
}

void OpenJabNab::NewHTTPConnection()
{
	HttpHandler * h = new HttpHandler(httpListener->nextPendingConnection(), httpApi, httpVioletApi);
	connect(this, SIGNAL(Quit()), h, SLOT(Disconnect()));
}

void OpenJabNab::NewXMPPConnection()
{
	XmppHandler * x = new XmppHandler(xmppListener->nextPendingConnection());
	connect(this, SIGNAL(Quit()), x, SLOT(Disconnect()));
}
