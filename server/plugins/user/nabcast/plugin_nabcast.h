#ifndef _PLUGINNABCAST_H_
#define _PLUGINNABCAST_H_

#include "plugininterface.h"

class PluginNabcast
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.nabcast" )

public:
  PluginNabcast();

  virtual bool Init(void) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnRFID(Ztamp *, Bunny *) override;
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;
  virtual const QString GetVersion(void) override { return "1.0.1"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.0.0", "Initial version");
    revisions.insert("1.0.1", "Fix bug for mp3 file listing");
    return revisions;
  }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    //list.insert("get", "");
    return list;
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

  virtual ~PluginNabcast() = default;

  QByteArray GetBroadcastHTTPUserPath(Bunny *, QString);
  bool playFile(Bunny *, QString);
  bool playRandomFile(Bunny *);
  QDir * GetUserDir(Bunny *);
  QDir nabcastFolder;
  QDir userFolder;

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_Register);
  PLUGIN_BUNNY_API_CALL(Api_Nabcast);
  PLUGIN_BUNNY_API_CALL(Api_File);
  PLUGIN_BUNNY_API_CALL(Api_Library);
};
#endif
