#ifndef _TTSACAPELA_H_
#define _TTSACAPELA_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "ttsinterface.h"

class TTSresponsivevoice : public TTSInterface
{
	Q_OBJECT
	Q_INTERFACES(TTSInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.tts.responsivevoice" FILE "")

public:
	TTSresponsivevoice();
	virtual ~TTSresponsivevoice();
	QString CreateNewSound(QString, QString, bool);

private:
};

#endif

