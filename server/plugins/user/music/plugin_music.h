#ifndef _PLUGINMUSIC_H_
#define _PLUGINMUSIC_H_

#include "plugininterface.h"

class PluginMusic
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.music" )

public:
  PluginMusic();

  virtual bool Init() override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnRFID(Ztamp *, Bunny *) override;
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;

  virtual const QString GetVersion(void) override { return "2.0.1"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("2.0.1", "Fix bug for mp3 file listing");
    return revisions;
  }

private:
  enum LibraryMode
  {
    NoLibrary,
    MixedLibrary    = 0b1,
    OwnLibrary      = 0b10,
    SharedLibrary   = 0b100,
    PrivateLibrary  = 0b1000
  };

  virtual ~PluginMusic() = default;

  QByteArray GetBroadcastHTTPUserPath(Bunny *, QString);
  bool playFile(Bunny *, QString);
  bool playRandomFile(Bunny *);
  bool playRandomInGroup(Bunny *, QString);
  QDir * GetUserDir(Bunny *);
  QDir musicFolder;
  QDir userFolder;

  // API
  void InitApiCalls();
/*
  PLUGIN_BUNNY_API_CALL(Api_Play);
  PLUGIN_BUNNY_API_CALL(Api_AddRFID);
  PLUGIN_BUNNY_API_CALL(Api_RemoveRFID);
  PLUGIN_BUNNY_API_CALL(Api_ListRFID);
  PLUGIN_BUNNY_API_CALL(Api_getFilesList);
  PLUGIN_BUNNY_API_CALL(Api_libraryMode);
*/
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_File);
  PLUGIN_BUNNY_API_CALL(Api_Library);

};
#endif
