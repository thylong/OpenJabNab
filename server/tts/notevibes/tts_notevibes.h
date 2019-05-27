#ifndef _TTSNOTEVIBES_H_
#define _TTSNOTEVIBES_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "ttsinterface.h"

class TTSNotevibes : public TTSInterface
{
	Q_OBJECT
	Q_INTERFACES(TTSInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.tts.notevibes" FILE "")

public:
	TTSNotevibes();
	virtual ~TTSNotevibes();
	QString CreateNewSound(QString, QString, bool);

private:
};

#endif
