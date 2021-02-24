#include <QCoreApplication>
#include <QDir>
#include <QSettings>
#include <iostream>
#include <cstdlib>
#include "log.h"
#include "settings.h"
GlobalSettings::GlobalSettings(const QString& cfgDir)
: _confDir(cfgDir)
{
	QString fileName = QDir(cfgDir).absoluteFilePath("openjabnab.ini");
	if (QFile::exists(fileName))
		settings = new QSettings(fileName, QSettings::IniFormat);
	else
	{
		std::cerr << "Error, openjabnab.ini not found !" << std::endl;
		exit(-1);
	}
}

GlobalSettings::~GlobalSettings()
{
	delete settings;
}


int GlobalSettings::Set(QString const& key, int i)
{
	int old = GetInt(key);
	instance->settings->setValue(key, i);
	return old;
}

QString GlobalSettings::Set(QString const& key, QString s)
{
	QString old = GetString(key);
	instance->settings->setValue(key, s);
	return old;
}

QStringList GlobalSettings::Set(QString const& key, QStringList s)
{
	QStringList old = GetStringList(key);
	instance->settings->setValue(key, s);
	return old;
}

QVariant GlobalSettings::Get(QString const& key, QVariant const& defaultValue)
{
	if (instance && instance->settings->contains(key))
		return instance->settings->value(key);
	else
		return defaultValue;
}

bool GlobalSettings::HasKey(QString const& key)
{
	return instance->settings->contains(key);
}

GlobalSettings * GlobalSettings::instance = 0;
