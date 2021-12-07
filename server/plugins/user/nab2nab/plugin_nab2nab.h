#ifndef _PLUGINNAB2NAB_H_
#define _PLUGINNAB2NAB_H_

#include "plugininterface.h"
#include "pluginmessageinterface.h"

class PluginNab2nab
  : public PluginInterface
  , private PluginMessageInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface PluginMessageInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.nab2nab" )

public:
  PluginNab2nab();

  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnRFID(Bunny *, QByteArray const&) override;
  virtual bool OnRecord(Bunny *, QString const&) override;

  virtual const QString GetVersion(void) override { return "1.2.2"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.2.2", "Add supported languages informations");
    return revisions;
  }

private:
  enum NabAnnounce { None, Repeat, Always };

  virtual ~PluginNab2nab() = default;

  void SendAudio(QString url);
  void SendMessage(QString text);
  QByteArray GetRecordBroadcastHTTPPath(QString f) const;
  QStringList announceText;

  // API
  virtual void InitApiCalls(void) override;

  PLUGIN_BUNNY_API_CALL(Api_SetPreference);
  PLUGIN_BUNNY_API_CALL(Api_GetPreference);
  PLUGIN_BUNNY_API_CALL(Api_AddFavorite);
  PLUGIN_BUNNY_API_CALL(Api_RemoveFavorite);
  PLUGIN_BUNNY_API_CALL(Api_GetFavorites);
  PLUGIN_BUNNY_API_CALL(Api_SetReceiver);
  PLUGIN_BUNNY_API_CALL(Api_RemoveReceiver);
  PLUGIN_BUNNY_API_CALL(Api_GetReceivers);

  PLUGIN_BUNNY_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_Friend);
/*
  PLUGIN_API_CALL(Api_SendMessage);
  PLUGIN_API_CALL(Api_SendAudio);
  PLUGIN_API_CALL(Api_ReceiveMessage);
*/
};

#endif
