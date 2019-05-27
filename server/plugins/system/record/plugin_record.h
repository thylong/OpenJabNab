#ifndef _PLUGINRECORD_H_
#define _PLUGINRECORD_H_

#include "plugininterface.h"
#include "httprequest.h"

class PluginRecord : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
    Q_PLUGIN_METADATA(IID "ojn.plugin.system.record" FILE "")

public slots:
	QStringList GetRecordList(Bunny *, int, int);

private slots:
	QString OnApiList(Bunny *, QVariant);

public:
	PluginRecord();
	virtual ~PluginRecord() {};
	virtual bool HttpRequestHandle(HTTPRequest &);

	QString GetVersion() { return "1.1.1"; }
	QHash<QString, QString> GetChangelog()
	{
		QHash<QString, QString> revisions;
		revisions.insert("1.1.0", "Add API to list records");
		revisions.insert("1.1.1", "Add list records to extended API");
		return revisions;
	}
	QStringList GetLanguages() { return QStringList() << "all"; }
        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                list.insert("list", "xml");
                return list;
        }

	//QStringList GetRecordList(Bunny * b, int o) { return GetRecordList(b, o, 20); };
	//QStringList GetRecordList(Bunny * b) { return GetRecordList(b, 0, 20); };

	// API
	void InitApiCalls();
	PLUGIN_BUNNY_API_CALL(Api_Record);
private:
	QDir recordFolder;
};

#endif
