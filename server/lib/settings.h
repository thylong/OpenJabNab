#ifndef _GLOBALSETTINGS_H_
#define _GLOBALSETTINGS_H_

#include <QObject>
#include <QSettings>
#include <QString>
#include <QStringList>
#include <QVariant>
#include "global.h"

class OJN_EXPORT GlobalSettings
{
public:
	~GlobalSettings();
	static void Init(const QString& cfgDir);
	static void Close();

  static QString GetConfigDir(void);

	static int Set(QString const& key, int);
	static QString Set(QString const& key, QString);
	static QStringList Set(QString const& key, QStringList);

	// Without default value
	static QVariant Get(QString const& key);
	static QStringList GetStringList(QString const& key);
	static QString GetString(QString const& key);
	static int GetInt(QString const& key);

	// With default value
	static QVariant Get(QString const& key, QVariant const& defaultValue);
	static QString GetString(QString const& key, QString const& defaultValue);
	static QStringList GetStringList(QString const& key, QStringList const& defaultValue);
	static int GetInt(QString const& key, int defaultValue);

	static bool HasKey(QString const& key);

private:
	GlobalSettings(const QString& cfgDir);
	static GlobalSettings * instance;
	QSettings * settings;
  QString _confDir;
};

inline QString GlobalSettings::GetConfigDir(void)
{
  return instance->_confDir;
}

inline void GlobalSettings::Init(const QString& cfgDir)
{
	if (!instance)
		instance = new GlobalSettings(cfgDir);
}

inline void GlobalSettings::Close()
{
	if (instance)
		delete instance;
}

inline QString GlobalSettings::GetString(QString const& key)
{
	return Get(key).toString();
}

inline QStringList GlobalSettings::GetStringList(QString const& key)
{
	return Get(key).toStringList();
}

inline int GlobalSettings::GetInt(QString const& key)
{
	return Get(key).toInt();
}

inline QString GlobalSettings::GetString(QString const& key, QString const& defaultValue)
{
	return Get(key, defaultValue).toString();
}

inline QStringList GlobalSettings::GetStringList(QString const& key, QStringList const& defaultValue)
{
	return Get(key, defaultValue).toStringList();
}

inline int GlobalSettings::GetInt(QString const& key, int defaultValue)
{
	return Get(key, defaultValue).toInt();
}
#endif
