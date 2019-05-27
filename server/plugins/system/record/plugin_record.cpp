#include <QDateTime>
#include <QStringList>
#include <memory>
#include "plugin_record.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "log.h"
#include "settings.h"
#include "translator.h"

PluginRecord::PluginRecord():PluginInterface("record", "Manage Record requests", SystemPlugin | ApiPlugin)
{
	std::unique_ptr<QDir> dir(GetLocalHTTPFolder());
	if(dir.get())
	{
		recordFolder = *dir;
	}
}

QString PluginRecord::OnApiList(Bunny *b, QVariant v)
{
	int offset = v.value<int>();

	QStringList list;
	QStringList nameFilter("record_"+QString(b->GetID())+"_*.wav");
	QDir directory(recordFolder.absolutePath());
	QStringList listAll = directory.entryList(nameFilter);
	foreach(QString file, listAll)
	{
		list.prepend(GetFullHTTPPath(file));
	}
	QString records = "<records>";
	foreach(QString record, list.mid(offset, 20))
	{
		records += "<record>" + record + "</record>";
	}
	records += "</records>";
	return records;
}

bool PluginRecord::HttpRequestHandle(HTTPRequest & request)
{
	QString uri = request.GetURI();
	if (uri.startsWith("/vl/record.jsp"))
	{
		QString serialnumber = request.GetArg("sn");
		QString filename ="record_"+serialnumber+"_"+QDateTime::currentDateTime().toString("yyyyMMdd_hhmmss")+".wav";
		QString filepath = recordFolder.absoluteFilePath(filename);
		QFile wavFile( filepath );
		if(wavFile.open(QFile::WriteOnly)) {
			wavFile.write(request.GetPostRaw());
			wavFile.close();
			Bunny * b = BunnyManager::GetBunny(this, serialnumber.toLatin1());
			b->SetGlobalSetting("LastRecord", filename);
			if (b->OnRecord(filename))
				return true;
		} else {
			LogError("Impossible to write record file");
		}
		return true;
	}
	return false;
}

QStringList PluginRecord::GetRecordList(Bunny * b, int offset, int limit)
{
	//LogDebug("Want record list for " + QString(b->GetID()) + " (" + QString::number(offset) + ", " + QString::number(limit) + ")");
	QStringList list;
	QStringList nameFilter("record_"+QString(b->GetID())+"_*.wav");
	//LogDebug("Filter is : " + nameFilter.join(", "));
	QDir directory(recordFolder.absolutePath());
	QStringList listAll = directory.entryList(nameFilter);
	foreach(QString file, listAll)
	{
		list.prepend(GetFullHTTPPath(file));
	}
	list = QStringList(list.mid(offset, limit));
	//LogDebug("List of records : " + list.join(", "));
	return list;
}

void PluginRecord::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("record()", PluginRecord, Api_Record);
}

PLUGIN_BUNNY_API_CALL(PluginRecord::Api_Record)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		int offset = 0;
		if(hRequest.HasArg("offset"))
			offset = hRequest.GetArg("offset").toInt();
		int limit = 20;
		if(hRequest.HasArg("limit"))
			limit = hRequest.GetArg("limit").toInt();
		return new ApiManager::ApiList(GetRecordList(bunny, offset, limit));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

