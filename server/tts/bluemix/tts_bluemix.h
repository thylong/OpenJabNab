#pragma once

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "ttsinterface.h"

class TTSbluemix : public TTSInterface
{
	Q_OBJECT
	Q_INTERFACES(TTSInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.tts.bluemix" )

public:
	TTSbluemix();
	virtual ~TTSbluemix();
	QString CreateNewSound(QString, QString, bool);

private:
};
