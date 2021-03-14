#include <QRegExp>
#include <QRandomGenerator>
#include "plugin_choregraphy.h"
#include "messagepacket.h"
#include "translator.h"

// P_L "+QString::number(QRandomGenerator::global()->generate() % 8).toLatin1()

PluginChoregraphy::PluginChoregraphy():PluginInterface("choregraphy", "Play choregraphy during sound playback", BunnyV2Plugin | DevPlugin)
{
}

PluginChoregraphy::~PluginChoregraphy()
{
}

void PluginChoregraphy::BeforeSendMessage(Bunny * b, MessagePacket * m, QString sender)
{
	QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Choregraphy", QMap<QString, QVariant>()).toMap();
	QString choregraphy = list.contains(sender) ? list.value(sender).toString() : QString();
	if(choregraphy.length() > 0)
	{
		if(choregraphy == "random")
		{
			choregraphy = QString::number(QRandomGenerator::global()->generate() % 8);
		}
		QRegExp rx("((MU|ST) .*\n)MW");
		rx.setMinimal(true);
		QString message = QString(m->GetPrintableData());
		message.replace(rx, "\\1PL " + choregraphy + "\nMW");
		m->SetMessage(message.toLatin1());
	}
}

bool PluginChoregraphy::Init()
{
	return true;
}

void PluginChoregraphy::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("config()", &PluginChoregraphy::Api_Config);
}

PLUGIN_BUNNY_API_CALL(PluginChoregraphy::Api_Config)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Choregraphy", QMap<QString, QVariant>()).toMap();
		return new ApiAnswers::MappedList(list);
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("sender"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sender", GetName()));

		QString sender = hRequest.GetArg("sender");

		if(!hRequest.HasArg("chor"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("chor", GetName()));

		QString chor = hRequest.GetArg("chor");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Choregraphy", QMap<QString, QVariant>()).toMap();
		list.insert(sender, chor);
		bunny->SetPluginSetting(GetName(), "Choregraphy", list);
		return new ApiAnswers::Ok(Translator::tr("Added choregraphy '%1' for bunny '%2'", account).arg(chor, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("sender"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sender", GetName()));

		QString sender = hRequest.GetArg("sender");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Choregraphy", QMap<QString, QVariant>()).toMap();
		int removed = list.remove(sender);
		if(removed > 0)
		{
			bunny->SetPluginSetting(GetName(), "Choregraphy", list);
			return new ApiAnswers::Ok(Translator::tr("Removed choregraphy '%1' for bunny '%2'", account).arg(sender, QString(bunny->GetID())));
		}
		return new ApiAnswers::Error(Translator::tr("Choregraphy '%1' not found for bunny %2", account).arg(sender, QString(bunny->GetID())));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}
