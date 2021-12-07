#ifndef _TTSPICO_H_
#define _TTSPICO_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>

#include "tts/ttsinterface.h"

class TTSPico : public TTSInterface
{
	Q_OBJECT
	Q_INTERFACES(TTSInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.tts.pico" )

public:
	TTSPico();
	virtual ~TTSPico();
	QString CreateNewSound(QString, QString, bool);

private:
};

#endif
