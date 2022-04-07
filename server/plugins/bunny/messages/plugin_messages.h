#ifndef _PLUGINMESSAGES_H_
#define _PLUGINMESSAGES_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>

#include "plugininterface.h"

class PluginMessages
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.messages" )

public:
  PluginMessages();

public slots:
  bool AddMessage(Bunny *, QString, QStringList, int);
  bool AddMessage(Bunny *, QString, QStringList);
  bool RemoveMessage(Bunny *, int);

public:
  virtual void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &) override;
  virtual bool OnClick(Bunny *, PluginInterface::ClickType) override;
  virtual void OnCron(Bunny * b, QVariant, unsigned int) override;
  virtual bool OnRFID(Bunny * b, QByteArray const& tag) override;
  virtual void OnBunnyConnect(Bunny *) override;
  virtual void OnBunnyDisconnect(Bunny *) override;
  virtual bool OnVoiceCommand(Bunny *, QString const&, QStringList const&) override;

  virtual const QString GetVersion(void) override { return "1.1.3"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }

private:
  virtual ~PluginMessages() = default;

  void PluginStateChanged();
  void sayMessages(Bunny *, QString);
  void sayMessages(Bunny *);

  bool SaveMessages(Bunny *, QStringList);
  int CountMessages(Bunny *);
  int CountMessages(Bunny *, QString);
  void CleanMessages(Bunny *);
  void CleanMessages(Bunny *, int);
  void CleanMessages(Bunny *, QString);
  void CleanMessages(Bunny *, QString, int);
  void ClearMessages(Bunny *);
  QStringList GetMessages(Bunny *);
  int GetKeepTime(Bunny *);
  int GetKeepTime(Bunny *, QString);

  QString getPlugin(QString);

  void MessageNotification(Bunny *);

  // API
  virtual void InitApiCalls(void) override;
  PLUGIN_API_CALL(Api_Config);
  PLUGIN_BUNNY_API_CALL(Api_Schedule);
  PLUGIN_BUNNY_API_CALL(Api_Option);
  PLUGIN_BUNNY_API_CALL(Api_RFID);
  PLUGIN_BUNNY_API_CALL(Api_Message);
};
#endif
