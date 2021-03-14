#ifndef _LOG_H_
#define _LOG_H_

#include <QString>

#include "global.h"

class OJN_EXPORT Log
{
public:
	enum LogLevel 
	{ 
		Log_None = 0, 
		Log_Info, 
		Log_Error, 
		Log_Warn, 
		Log_Debug
	};

	static void LogToFile(QString const&, LogLevel);

private:
	Log();
	LogLevel GetLevel(QString const&);

	LogLevel maxFileLogLevel;
	LogLevel maxScreenLogLevel;
};

#define LogInfo(data) 		Log::LogToFile(data, Log::Log_Info)
#define LogError(data) 		Log::LogToFile(QString("%1 : %2").arg(Q_FUNC_INFO,data), Log::Log_Error)
#define LogWarning(data) 	Log::LogToFile(QString("%1 : %2").arg(Q_FUNC_INFO,data), Log::Log_Warn)
#define LogDebug(data) 		Log::LogToFile(QString("%1 : %2").arg(Q_FUNC_INFO,data), Log::Log_Debug)

#include "QsLog/QsLog.h"
#define LogSentence(...) QsLogging::Logger::SentenceLog(__VA_ARGS__)
#define LogDump(...)     QsLogging::Logger::DumpLog(__VA_ARGS__)
#define LogCron(...)     QsLogging::Logger::CronLog(__VA_ARGS__)
#define LogTTS(...)      QsLogging::Logger::TTSLog(__VA_ARGS__)
#define LogBoot(...)     QsLogging::Logger::BootLog(__VA_ARGS__)
#define LogVoice(...)    QsLogging::Logger::VoiceLog(__VA_ARGS__)
#endif
