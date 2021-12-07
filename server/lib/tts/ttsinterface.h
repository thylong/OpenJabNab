#ifndef _TTSINTERFACE_H_
#define _TTSINTERFACE_H_

#include <QByteArray>
#include <QCoreApplication>
#include <QDir>
#include <QSettings>
#include <QString>
#include <QtPlugin>
#include "log.h"
#include "settings.h"
#include "voice.h"

class Account; 

class TTSInterface : public QObject
{
	friend class TTSManager;
public:

	TTSInterface(QString name, QString visualName = QString());
	virtual ~TTSInterface();
	
	// Called to init tts, return false if something is wrong
	virtual bool Init() { return true; };

	virtual QString CreateNewSound(QString, QString, bool) { return ""; }

	// Settings
	QVariant GetSettings(QString const& key, QVariant const& defaultValue = QVariant()) const;
	void SetSettings(QString const& key, QVariant const& value);

	// Plugin's name
	QString const& GetName() const;
	QString const& GetVisualName() const;

	// Plugin enable/disable functions
	bool GetEnable() const;

	// Send url toiask to bunny instead of file
	virtual bool canSendUrl() { return false; }
	virtual QByteArray SendSoundUrl(QString, QString, bool) { return ""; }

	QStringList GetLanguageList();
	QStringList GetVoiceList(QString language);
	QMap<QString, QString> GetVoiceListWithName(QString language);
	QMap<QString, QMap<QString, QString> > GetAllVoices();
	Voice GetVoice(QString name);
	Voice GetBestVoice(QString language);
	Voice GetBestVoice(QString language, Voice::VoiceGenre genre);

protected:
	void SetEnable(bool);
	QMap<QString, Voice> voiceList;
	QDir ttsFolder;
	QString ttsHTTPUrl;

	QSettings * settings;

private:
	QString ttsName;
	QString ttsVisualName;
	bool ttsEnable;
};

#include "ttsinterface_inline.h"

Q_DECLARE_INTERFACE(TTSInterface,"org.toms.openjabnab.TTSInterface/1.0")

#endif
