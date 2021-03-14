// Copyright (c) 2013, Razvan Petru
// All rights reserved.

// Redistribution and use in source and binary forms, with or without modification,
// are permitted provided that the following conditions are met:

// * Redistributions of source code must retain the above copyright notice, this
//   list of conditions and the following disclaimer.
// * Redistributions in binary form must reproduce the above copyright notice, this
//   list of conditions and the following disclaimer in the documentation and/or other
//   materials provided with the distribution.
// * The name of the contributors may not be used to endorse or promote products
//   derived from this software without specific prior written permission.

// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
// ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
// WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
// IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
// INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
// BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
// DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
// LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
// OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

/*
   QsLogging::Logger& logger = QsLogging::Logger::instance();

   // set file name
   const QString sLogPath(QDir("path").filePath("log.txt"));

   // Create log destinations
   QsLogging::DestinationPtr fileDestination(
      QsLogging::DestinationFactory::MakeFileDestination(sLogPath) );
   QsLogging::DestinationPtr debugDestination(
      QsLogging::DestinationFactory::MakeDebugOutputDestination() );

   // set log destinations on the logger
   logger.addDestination(debugDestination.get());
   logger.addDestination(fileDestination.get());

// write an info message
	QsLogging::Logger::Helper().stream() << "coucou";
   return 0;

*/

#include "QsLog.h"
#include "QsLogDest.h"
#include <QThreadPool>
#include <QRunnable>
#include <QMutex>
#include <QVector>
#include <QDir>
#include <QCoreApplication>
#include <QDate>
#include <QtGlobal>
#include <cassert>
#include <cstdlib>
#include <stdexcept>

namespace QsLogging
{
typedef QVector<DestinationPtr> DestinationList;

static const int CronInt = 1;
static const int DebugInt = 2;
static const int TTSInt  = 3;
static const int VoiceInt  = 4;
static const int DumpInt = 5;
static const int BootInt = 6;
static const int LogInt = 7;
static const int SentenceInt = 8;

// not using Qt::ISODate because we need the milliseconds too
//static const QString fmtDateTime("yyyy-MM-ddThh:mm:ss.zzz");
static const QString fmtDateTime("dd/MM/yyyy hh:mm:ss.zzz");

static Logger*  cronInstance;
static Logger*  logInstance;
static Logger*   ttsInstance;
static Logger* voiceInstance;
static Logger* debugInstance;
static Logger*  dumpInstance;
static Logger*  bootInstance;
static Logger*  sentenceInstance;

class LogWriterRunnable : public QRunnable
{
public:
    LogWriterRunnable(QString message, int instance);
    virtual void run();

private:
    QString mMessage;
    int nInstance;
};

class LoggerImpl
{
public:
    LoggerImpl(int);

    QThreadPool threadPool;
    QMutex logMutex;
    DestinationList destList;
    int nInstance;
};

LogWriterRunnable::LogWriterRunnable(QString message, int instance)
    : QRunnable()
    , mMessage(message)
    , nInstance(instance)
{
}

void LogWriterRunnable::run()
{
    Logger::instance(nInstance).write(mMessage);
}


LoggerImpl::LoggerImpl(int instance)
{
    nInstance = instance;
    // assume at least file + console
    destList.reserve(2);
    threadPool.setMaxThreadCount(1);
    threadPool.setExpiryTimeout(-1);
}


Logger::Logger(int instance)
    : d(new LoggerImpl(instance))
    , nInstance(instance)
{
}

Logger& Logger::instance(int instance)
{
    switch (instance) {
        case TTSInt: {
            if (!ttsInstance)
                ttsInstance = new Logger(instance);

            return *ttsInstance;
        }
        case VoiceInt: {
            if (!voiceInstance)
                voiceInstance = new Logger(instance);

            return *voiceInstance;
        }
        case LogInt: {
            if (!logInstance)
                logInstance = new Logger(instance);

            return *logInstance;
        }
        case CronInt: {
            if (!cronInstance)
                cronInstance = new Logger(instance);

            return *cronInstance;
        }
        case DumpInt: {
            if (!dumpInstance)
	        dumpInstance = new Logger(instance);

            return *dumpInstance;
        }
        case BootInt: {
            if (!bootInstance)
	        bootInstance = new Logger(instance);

            return *bootInstance;
        }
        case SentenceInt: {
            if (!sentenceInstance)
	        sentenceInstance = new Logger(instance);

            return *sentenceInstance;
        }
        case DebugInt:
        default: {
            if (!debugInstance)
                debugInstance = new Logger(instance);

            return *debugInstance;
        }
    }
}

void Logger::destroyInstance()
{
    delete cronInstance;
    delete logInstance;
    delete debugInstance;
    delete ttsInstance;
    delete dumpInstance;
    delete voiceInstance;
    delete bootInstance;
    delete sentenceInstance;
}

Logger::~Logger()
{
    d->threadPool.waitForDone();
    delete d;
    d = 0;
}

void Logger::Init(const QString& logPath)
{
   QsLogging::Logger& LogLogger = QsLogging::Logger::instance(LogInt);
   QsLogging::Logger& CronLogger = QsLogging::Logger::instance(CronInt);
   QsLogging::Logger& VoiceLogger = QsLogging::Logger::instance(VoiceInt);
   QsLogging::Logger& DumpLogger = QsLogging::Logger::instance(DumpInt);
   QsLogging::Logger& TTSLogger = QsLogging::Logger::instance(TTSInt);
   QsLogging::Logger& DebugLogger = QsLogging::Logger::instance(DebugInt);
   QsLogging::Logger& BootLogger = QsLogging::Logger::instance(BootInt);
   QsLogging::Logger& SentenceLogger = QsLogging::Logger::instance(SentenceInt);

   auto logDir = QDir(logPath);
  if(!logDir.exists())
  {
    if(!logDir.mkpath(logPath))
    {
      Log("Unable to create logs directory !\n");
			exit(-1);
    }
  }
   const QString logLogPath(logDir.filePath("openjabnab.log"));
   const QString cronLogPath(logDir.filePath("cron.log"));
   const QString voiceLogPath(logDir.filePath("voice.log"));
   const QString dumpLogPath(logDir.filePath("dump.log"));
   const QString ttsLogPath(logDir.filePath("tts.log"));
   const QString debugLogPath(logDir.filePath("debug.log"));
   const QString bootLogPath(logDir.filePath("boot.log"));
   const QString sentenceLogPath(logDir.filePath("sentence.log"));

   // Create log destinations
   QsLogging::DestinationPtr logFileDestination( QsLogging::DestinationFactory::MakeFileDestination(logLogPath, QsLogging::EnableLogRotation) );
   QsLogging::DestinationPtr cronFileDestination( QsLogging::DestinationFactory::MakeFileDestination(cronLogPath, QsLogging::EnableLogRotation) );
   QsLogging::DestinationPtr voiceFileDestination( QsLogging::DestinationFactory::MakeFileDestination(voiceLogPath, QsLogging::EnableLogRotation) );
   QsLogging::DestinationPtr dumpFileDestination( QsLogging::DestinationFactory::MakeFileDestination(dumpLogPath, QsLogging::EnableLogRotation) );
   QsLogging::DestinationPtr ttsFileDestination( QsLogging::DestinationFactory::MakeFileDestination(ttsLogPath, QsLogging::EnableLogRotation) );
   QsLogging::DestinationPtr debugFileDestination( QsLogging::DestinationFactory::MakeFileDestination(debugLogPath, QsLogging::EnableLogRotation) );
   QsLogging::DestinationPtr bootFileDestination( QsLogging::DestinationFactory::MakeFileDestination(bootLogPath, QsLogging::EnableLogRotation) );
   QsLogging::DestinationPtr sentenceFileDestination( QsLogging::DestinationFactory::MakeFileDestination(sentenceLogPath, QsLogging::EnableLogRotation) );

   QsLogging::DestinationPtr debugDestination( QsLogging::DestinationFactory::MakeDebugOutputDestination() );

   // set log destinations on the logger

   LogLogger.addDestination(logFileDestination);
   //LogLogger.addDestination(debugDestination);

   CronLogger.addDestination(cronFileDestination);
   //CronLogger.addDestination(debugDestination);

   VoiceLogger.addDestination(voiceFileDestination);
   //VoiceLogger.addDestination(debugDestination);

   DumpLogger.addDestination(dumpFileDestination);
   //DumpLogger.addDestination(debugDestination);

   TTSLogger.addDestination(ttsFileDestination);
   //TTSLogger.addDestination(debugDestination);

   DebugLogger.addDestination(debugFileDestination);
   //DebugLogger.addDestination(debugDestination);

   BootLogger.addDestination(bootFileDestination);
   //BootLogger.addDestination(debugDestination);

   SentenceLogger.addDestination(sentenceFileDestination);
   //BootLogger.addDestination(debugDestination);

}

void Logger::Log(QString message)
{
    const QString completeMessage(QString(" %1")
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(LogInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::CronLog(QString message)
{
    const QString completeMessage(QString("[%1] %2")
                                  .arg("            ")
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(CronInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::CronLog(QString message, QByteArray bunny)
{
    const QString completeMessage(QString("[%1] %2")
                                  .arg(bunny.length() ? QString(bunny).toLower() : "            ")
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(CronInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::BootLog(QString message, QByteArray bunny)
{
    const QString completeMessage(QString("[%1] %2")
                                  .arg(bunny.length() ? QString(bunny).toLower() : "            ")
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(BootInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::SentenceLog(QString message, QString lng1, QString lng2)
{
    const QString completeMessage(QString("[%1][%2] %3")
                                  .arg(lng1.leftJustified(2, ' ').left(2))
                                  .arg(lng2.leftJustified(2, ' ').left(2))
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(SentenceInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::DebugLog(QString message, QString from)
{
    const QString completeMessage(QString("[%1] %2")
                                  .arg(from.leftJustified(15, ' ').left(15))
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(DebugInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::TTSLog(QByteArray bunny, QString from, TTSAnswer answer)
{
    const QString completeMessage(QString("[%1][%2][%3:%4] %5 (%6)")
                                  .arg(QString(bunny).toLower().leftJustified(12, ' ').left(12))
                                  .arg(from.leftJustified(12, ' ').left(12))
				  .arg(answer.returnedLanguage)
                                  .arg(answer.returnedVoice.leftJustified(20, ' ').left(20))
                                  .arg(answer.text)
                                  .arg(answer.filePath)
                                  );
	QsLogging::Logger::Helper(TTSInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::VoiceLog(QString message, QByteArray bunny, QString language)
{
    const QString completeMessage(QString("[%1][%2] %3")
                                  .arg(QString(bunny).toLower())
                                  .arg(language)
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(VoiceInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::DumpLog(QString message, QString type)
{
    const QString completeMessage(QString("[%1] %2")
                                  .arg(type.leftJustified(24, ' ').left(24))
                                  .arg(message)
                                  );
	QsLogging::Logger::Helper(DumpInt).stream() << completeMessage.toStdString().c_str();
}

void Logger::addDestination(DestinationPtr destination)
{
    assert(destination.data());
    d->destList.push_back(destination);
}

//! creates the complete log message and passes it to the logger
void Logger::Helper::writeToLog()
{
    const QString completeMessage(QString("[%1]%2")
                                  .arg(QDateTime::currentDateTime().toString(fmtDateTime))
                                  .arg(buffer)
                                  );

    Logger::instance(nInstance).enqueueWrite(completeMessage);
}

Logger::Helper::~Helper()
{
    try {
        writeToLog();
    }
    catch(std::exception&) {
        // you shouldn't throw exceptions from a sink
        assert(!"exception in logger helper destructor");
       // throw;
    }
}

//! directs the message to the task queue or writes it directly
void Logger::enqueueWrite(const QString& message)
{
    LogWriterRunnable *r = new LogWriterRunnable(message, nInstance);
    d->threadPool.start(r);
}

//! Sends the message to all the destinations. The level for this message is passed in case
//! it's useful for processing in the destination.
void Logger::write(const QString& message)
{
    QMutexLocker lock(&d->logMutex);
    for (DestinationList::iterator it = d->destList.begin(),
        endIt = d->destList.end();it != endIt;++it) {
        (*it)->write(message);
    }
}

} // end namespace
