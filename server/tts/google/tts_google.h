#ifndef _TTSGOOGLE_H_
#define _TTSGOOGLE_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include <QNetworkAccessManager>
#include <QNetworkReply>

#include "tts/ttsinterface.h"

class TTSGoogle : public TTSInterface
{
	Q_OBJECT
	Q_INTERFACES(TTSInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.tts.google" )

public:
	TTSGoogle();
	virtual ~TTSGoogle();
	QString CreateNewSound(QString, QString, bool);

private:
	QNetworkAccessManager *networkManager;
	QString getApiKey();
	bool downloadGoogleTTS(const QString &text, const QString &voice, const QString &outputFile);
	void initializeGoogleVoices();
};

#endif