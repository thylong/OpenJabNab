#ifndef _TTSNOTEVIBES_H_
#define _TTSNOTEVIBES_H_

#include <QNetworkAccessManager>

#include "ttsinterface.h"

class TTSNotevibes 
  : public TTSInterface
{
  Q_OBJECT
  Q_INTERFACES(TTSInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.tts.notevibes" )

public:
  TTSNotevibes();
  virtual ~TTSNotevibes();
  QString CreateNewSound(QString, QString, bool);

private:
  QNetworkAccessManager _http;
};

#endif
