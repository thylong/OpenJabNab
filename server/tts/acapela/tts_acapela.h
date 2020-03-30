#pragma once

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "ttsinterface.h"

class TTSacapela : public TTSInterface
{
	Q_OBJECT
	Q_INTERFACES(TTSInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.tts.acapela" )

public:
	TTSacapela();
	virtual ~TTSacapela();
	QString CreateNewSound(QString, QString, bool);

private:
};