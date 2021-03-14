#include <QCommandLineParser>
#include <QDebug>
#include <QTcpServer>
#include <QTimer>
#include <QString>
#include <QSql>



#include "accountmanager.h"
#include "browsercache.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "dbmanager.h"
#include "httphandler.h"
#include "log.h"
#include "nabaztagmanager.h"
#include "openjabnab.h"
//#include "netdump.h"
#include "pluginmanager.h"
#include "settings.h"
#include "sentencemanager.h"
#include "translator.h"
#include "ttsmanager.h"
#include "xmpphandler.h"
#include "ztamp.h"
#include "ztampmanager.h"


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
	BrowserCache::Init(this);
	PluginManager::Init();
	BunnyManager::LoadBunnies();
	ZtampManager::LoadZtamps();
	ApiManager::InitApiCalls();

	if(GlobalSettings::Get("Config/HttpListener", true) == true)
	{
		auto httpApi = GlobalSettings::Get("Config/HttpApi", true).toBool();
		auto httpVioletApi = GlobalSettings::Get("Config/HttpVioletApi", true).toBool();
		LogInfo(QString("Parsing of HTTP Api is ").append(httpApi?"enabled":"disabled"));
		LogInfo(QString("Parsing of HTTP VioletApi is ").append(httpVioletApi?"enabled":"disabled"));

		// Create Listeners
		httpListener = new QTcpServer(this);
		httpListener->setMaxPendingConnections(GlobalSettings::GetInt("OpenJabNabServers/HTTPMaxPendingConnections", 30));
		httpListener->listen(QHostAddress::LocalHost, GlobalSettings::GetInt("OpenJabNabServers/ListeningHttpPort", 8080));
		QObject::connect(httpListener, &QTcpServer::newConnection, [&,httpApi,httpVioletApi](void)
		{
			auto* it = new HttpHandler(httpListener->nextPendingConnection(), httpApi, httpVioletApi);
			//LogDebug(QString("New HTTPHandler 0x%1").arg((quintptr)it, QT_POINTER_SIZE * 2, 16, QChar('0')));
			_httpHandlers.emplace_back(it);
		});
	}
	else
		LogWarning("Warning : HTTP Listener is disabled !");

	if(GlobalSettings::Get("Config/XmppListener", true) == true)
	{
		int port = GlobalSettings::GetInt("OpenJabNabServers/ListeningXmppPort", 5222);
		LogInfo(QString("XMPP Port is: %1").arg(port));
		xmppListener = new QTcpServer(this);
		xmppListener->setMaxPendingConnections(GlobalSettings::GetInt("OpenJabNabServers/XMPPMaxPendingConnections", 30));
		xmppListener->listen(QHostAddress::Any, port);
		QObject::connect(xmppListener, &QTcpServer::newConnection, [&](void)
		{
			auto* it = new XmppHandler(xmppListener->nextPendingConnection());
			//LogDebug(QString("New XMPPHandler 0x%1").arg((quintptr)it, QT_POINTER_SIZE * 2, 16, QChar('0')));
			_xmppHandlers.emplace_back(it);
		});
	}
	else
		LogWarning("Warning : XMPP Listener is disabled !");

	autoSaveTmr.setInterval(GlobalSettings::GetInt("Timer/Autosave", 5*60) * 1000);	// 5min
	QObject::connect(&autoSaveTmr,&QTimer::timeout, [&](void)
	{
		AccountManager::Instance().SaveAccounts();
		BunnyManager::SaveBunnies();
		ZtampManager::SaveZtamps();
	});


	nabStatusTmr.setInterval(GlobalSettings::GetInt("Timer/UpdateV1", 60) * 1000);	// 1min
	QObject::connect(&nabStatusTmr,&QTimer::timeout, [&](void)
	{
		NabaztagManager::Instance().UpdateStatus();
	});

	timeoutTmr.setInterval(GlobalSettings::GetInt("Timer/TimeoutsCleanup", 10) * 1000);	// 10s
	QObject::connect(&timeoutTmr, &QTimer::timeout, [&](void)
	{
		{
			auto it = _httpHandlers.begin();
			const auto& end = _httpHandlers.end();
			while(it != end)
			{
				if((*it)->shouldDelete())
				{
					//LogDebug(QString("Should delete HTTPHandler 0x%1").arg((quintptr)*it, T_POINTER_SIZE * 2, 16, QChar('0')));
					(*it)->cleanup();
					it = _httpHandlers.erase(it);
				}
				else
					++it;
			}
		}
		{
			auto it = _xmppHandlers.begin();
			const auto& end = _xmppHandlers.end();
			while(it != end)
			{
				if((*it)->shouldDelete())
				{
					//LogDebug(QString("Should delete XMPPHandler 0x%1").arg((quintptr)*it, QT_POINTER_SIZE * 2, 16, QChar('0')));
					(*it)->cleanup();
					it = _xmppHandlers.erase(it);
				}				
				else
					++it;
			}
		}
	});

	autoSaveTmr.start();
	nabStatusTmr.start();
	timeoutTmr.start();
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
		for(auto& it: _xmppHandlers)
			it->cleanup();
		xmppListener->close();
	}
	if(httpListener)
	{
		for(auto& it: _httpHandlers)
			it->cleanup();
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
	LogInfo("-- OpenJabNab Close --");

	QsLogging::Logger::destroyInstance();
}
