#include <iostream>

#include "QsLog/QsLog.h"

#include "log.h"
#include "settings.h"

Log::Log()
{
  maxFileLogLevel 	= GetLevel(GlobalSettings::GetString("Log/LogFileLevel",   "Debug"  ));
  maxScreenLogLevel = GetLevel(GlobalSettings::GetString("Log/LogScreenLevel", "Warning"));
}

void Log::LogToFile(QString const& data, LogLevel level)
{
  static Log instance;

  if (level <= instance.maxFileLogLevel)
    QsLogging::Logger::Log(data);

  if (level <= instance.maxScreenLogLevel)
    std::cout << qPrintable(data) << std::endl;
}

Log::LogLevel Log::GetLevel(QString const& level)
{
  if (level.compare("debug", Qt::CaseInsensitive) == 0)
    return Log_Debug;
  if (level.compare("warning", Qt::CaseInsensitive) == 0)
    return Log_Warn;
  if (level.compare("error", Qt::CaseInsensitive) == 0)
    return Log_Error;
  if (level.compare("info", Qt::CaseInsensitive) == 0)
    return Log_Info;
  return Log_None;
}
