#ifndef _PLUGINRECORD_H_
#define _PLUGINRECORD_H_

#include "plugininterface.h"

class PluginRecord : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.system.record" )

public:
  PluginRecord();

  virtual bool HttpRequestHandle(HTTPRequest &) override;

  virtual const QString GetVersion(void) override { return "1.1.1"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("1.1.0", "Add API to list records");
    revisions.insert("1.1.1", "Add list records to extended API");
    return revisions;
  }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("list", "xml");
    return list;
  }

private:
  virtual ~PluginRecord() = default;

  QString OnApiList(Bunny *, QVariant);
  QStringList GetRecordList(Bunny *, int, int);

  // API
  void InitApiCalls();
  PLUGIN_BUNNY_API_CALL(Api_Record);

  QDir recordFolder;
};

#endif
