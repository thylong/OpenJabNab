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

#ifndef QSLOG_H
#define QSLOG_H

#include "QsLogDest.h"
#include <QDebug>
#include <QString>

#include "tts/ttsanswer.h"

#define QS_LOG_VERSION "1.166"

#define QS_LOG_LOG	"openjabnab.log"
#define QS_CRON_LOG	"cron.log"
#define QS_TTS_LOG	"tts.log"
#define QS_VOICE_LOG	"voice.log"
#define QS_DEBUG_LOG	"debug.log"
#define QS_DUMP_LOG	"dump.log"
#define QS_BOOT_LOG	"boot.log"
#define QS_SENTENCE_LOG	"sentence.log"

namespace QsLogging
{
class Destination;
class LoggerImpl; // d pointer

class QSLOG_SHARED_OBJECT Logger
{
public:
    static Logger& instance(int);
    static void destroyInstance();

    ~Logger();

    //! Adds a log message destination. Don't add null destinations.
    void addDestination(DestinationPtr destination);

    //! The helper forwards the streaming to QDebug and builds the final
    //! log message.
    class QSLOG_SHARED_OBJECT Helper
    {
    public:
        explicit Helper(int instance) :
	    nInstance(instance),
            qtDebug(&buffer) {}
        ~Helper();
        QDebug& stream(){ return qtDebug; }

    private:
        void writeToLog();
        int nInstance;

        QString buffer;
        QDebug qtDebug;
    };

    static void Init(const QString& logPath);
    static void CronLog(QString, QByteArray);
    static void CronLog(QString);
    static void Log(QString);
    static void DebugLog(QString, QString);
    static void TTSLog(QByteArray, QString, TTSAnswer);
    static void VoiceLog(QString, QByteArray, QString);
    static void DumpLog(QString, QString);
    static void BootLog(QString, QByteArray);
    static void SentenceLog(QString, QString, QString);

private:
    Logger(int);

    void enqueueWrite(const QString& message);
    void write(const QString& message);

    LoggerImpl* d;
    int nInstance;

    friend class LogWriterRunnable;
};


} // end namespace


#endif // QSLOG_H
