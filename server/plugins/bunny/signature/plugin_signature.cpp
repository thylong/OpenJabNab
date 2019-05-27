#include <QCryptographicHash>
#include "plugin_signature.h"
#include "messagepacket.h"
#include "translator.h"

PluginSignature::PluginSignature():PluginInterface("signature", "Add signature before or after a message", BunnyV2Plugin)
{
}

PluginSignature::~PluginSignature()
{
}

void PluginSignature::BeforeSendMessage(Bunny * b, MessagePacket * m, QString sender)
{
	QMap<QString, QVariant> userSounds = b->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap();

	QMap<QString, QVariant> befores = b->GetPluginSetting(GetName(), "Before", QMap<QString, QVariant>()).toMap();
	QString before = befores.contains(sender) ? befores.value(sender).toString() : QString();
	if(before.length() > 0)
	{
		bool beforeAdded = false;
		QString beforeSound = "";
		if(userSounds.contains(before))
		{
 			beforeSound = userSounds.value(before).toString();
			if(beforeSound.length() > 0)
			{
				QByteArray message = m->GetPrintableData();
				if(beforeSound.startsWith("http"))
				{
					message = "MC " + beforeSound.toLatin1() + "\nMW\n" + m->GetPrintableData();
				}
				else
				{
					message = "MC " + GetBroadcastHTTPUserPath(b, beforeSound) + "\nMW\n" + m->GetPrintableData();
				}
				m->SetMessage(message);
				beforeAdded = true;
			}
		}
		else if(pluginSounds.contains(before))
		{
 			beforeSound = pluginSounds.value(before).toString();
			if(beforeSound.length() > 0)
			{
				QByteArray message = m->GetPrintableData();
				if(beforeSound.startsWith("http"))
				{
					message = "MC " + beforeSound.toLatin1() + "\nMW\n" + m->GetPrintableData();
				}
				else
				{
					message = "MC " + GetBroadcastHTTPPath(beforeSound) + "\nMW\n" + m->GetPrintableData();
				}
				m->SetMessage(message);
				beforeAdded = true;
			}
		}
		if(!beforeAdded)
		{
			LogError(QString("Sound not available : %1").arg(before));
		}
	}

	QMap<QString, QVariant> afters = b->GetPluginSetting(GetName(), "After", QMap<QString, QVariant>()).toMap();
	QString after = afters.contains(sender) ? afters.value(sender).toString() : QString();
	if(after.length() > 0)
	{
		bool afterAdded = false;
		QString afterSound = "";
		if(userSounds.contains(after))
		{
 			afterSound = userSounds.value(after).toString();
			if(afterSound.length() > 0)
			{
				QByteArray message = m->GetPrintableData();
				if(afterSound.startsWith("http"))
				{
					message = m->GetPrintableData() + "MC " + afterSound.toLatin1() + "\nMW\n";
				}
				else
				{
					message = m->GetPrintableData() + "MC " + GetBroadcastHTTPUserPath(b, afterSound) + "\nMW\n";
				}
				m->SetMessage(message);
				afterAdded = true;
			}
		}
		else if(pluginSounds.contains(after))
		{
 			afterSound = pluginSounds.value(after).toString();
			if(afterSound.length() > 0)
			{
				QByteArray message = m->GetPrintableData();
				if(afterSound.startsWith("http"))
				{
					message = m->GetPrintableData() + "MC " + afterSound.toLatin1() + "\nMW\n";
				}
				else
				{
					message = m->GetPrintableData() + "MC " + GetBroadcastHTTPPath(afterSound) + "\nMW\n";
				}
				m->SetMessage(message);
				afterAdded = true;
			}
		}
		if(!afterAdded)
		{
			LogError(QString("Sound not available : %1").arg(after));
		}
	}
}

QByteArray PluginSignature::GetBroadcastHTTPUserPath(Bunny * b, QString f)
{
	QString userName = QCryptographicHash::hash(b->GetGlobalSetting("OwnerAccount").toString().toLatin1(), QCryptographicHash::Md5).toHex();
	QString userFolder = QString("%1/%2/%3").arg(GlobalSettings::GetString("Config/HttpRoot"), GlobalSettings::GetString("Config/HttpUsersFolder"), userName);
	return QString("broadcast/%1/%2").arg(userFolder, f).toLatin1();
}

bool PluginSignature::Init()
{
	pluginSounds = GetSettings("Sounds", QMap<QString, QVariant>()).toMap();
	return true;
}

void PluginSignature::InitApiCalls()
{
	DECLARE_PLUGIN_BUNNY_API_CALL("config()", PluginSignature, Api_Config);
	DECLARE_PLUGIN_BUNNY_API_CALL("sound()", PluginSignature, Api_Sound);
	DECLARE_PLUGIN_API_CALL("sound()", PluginSignature, Api_PluginSound);
}

PLUGIN_BUNNY_API_CALL(PluginSignature::Api_Config)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		QMap<QString, QVariant> befores = bunny->GetPluginSetting(GetName(), "Before", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant> afters = bunny->GetPluginSetting(GetName(), "After", QMap<QString, QVariant>()).toMap();
		QMap<QString, QVariant> list;

		QMapIterator<QString, QVariant> i(befores);
		while (i.hasNext())
		{
			i.next();
			list.insert("before_" + i.key(), i.value());
		}
		QMapIterator<QString, QVariant> j(afters);
		while (j.hasNext())
		{
			j.next();
			list.insert("after_" + j.key(), j.value());
		}
		return new ApiManager::ApiMappedList(list);
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("type"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("type", GetName()));

		QString type = hRequest.GetArg("type");

		if(!hRequest.HasArg("sender"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sender", GetName()));

		QString sender = hRequest.GetArg("sender");

		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");

		if(type == "before")
		{
			QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Before", QMap<QString, QVariant>()).toMap();
			list.insert(sender, name);
			bunny->SetPluginSetting(GetName(), "Before", list);
			return new ApiManager::ApiOk(Translator::tr("Added signature '%1' for bunny '%2'", account).arg(name, QString(bunny->GetID())));
		}
		else if(type == "after")
		{
			QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "After", QMap<QString, QVariant>()).toMap();
			list.insert(sender, name);
			bunny->SetPluginSetting(GetName(), "After", list);
			return new ApiManager::ApiOk(Translator::tr("Added signature '%1' for bunny '%2'", account).arg(name, QString(bunny->GetID())));
		}
		else
		{
			return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("type", GetName()));
		}
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("type"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("type", GetName()));

		QString type = hRequest.GetArg("type");

		if(!hRequest.HasArg("sender"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("sender", GetName()));

		QString sender = hRequest.GetArg("sender");

		if(type == "before")
		{
			QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Before", QMap<QString, QVariant>()).toMap();
			int removed = list.remove(sender);
			if(removed > 0)
			{
				bunny->SetPluginSetting(GetName(), "Before", list);
				return new ApiManager::ApiOk(Translator::tr("Removed signature '%1' for bunny '%2'", account).arg(sender, QString(bunny->GetID())));
			}
			return new ApiManager::ApiError(Translator::tr("Signature '%1' not found for bunny %2", account).arg(sender, QString(bunny->GetID())));
		}
		else if(type == "after")
		{
			QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "After", QMap<QString, QVariant>()).toMap();
			int removed = list.remove(sender);
			if(removed > 0)
			{
				bunny->SetPluginSetting(GetName(), "After", list);
				return new ApiManager::ApiOk(Translator::tr("Removed signature '%1' for bunny '%2'", account).arg(sender, QString(bunny->GetID())));
			}
			return new ApiManager::ApiError(Translator::tr("Signature '%1' not found for bunny %2", account).arg(sender, QString(bunny->GetID())));
		}
		else
		{
			return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("type", GetName()));
		}
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_BUNNY_API_CALL(PluginSignature::Api_Sound)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(bunny->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		QString file = "";
		if(hRequest.HasArg("url"))
		{
			file = hRequest.GetArg("url");

			if(file.left(7) != "http://")
				return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("url", GetName()));
		}
		else if (hRequest.HasArg("file"))
		{
			file = hRequest.GetArg("file");
		}
		else
		{
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' or '%2' for plugin %3", account).arg("url", "file", GetName()));
		}

		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString	name = hRequest.GetArg("name");

		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap();
		if(!list.contains(name))
		{
			list.insert(name, file);
			bunny->SetPluginSetting(GetName(), "Sounds", list);
			return new ApiManager::ApiOk(Translator::tr("Added file '%1' for bunny '%2'", account).arg(file, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("File '%1' already exists for bunny '%2'", account).arg(name, QString(bunny->GetID())));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");
		QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Sounds", QMap<QString, QVariant>()).toMap();
		int removed = list.remove(name);
		if(removed > 0)
		{
			bunny->SetPluginSetting(GetName(), "Sounds", list);
			return new ApiManager::ApiOk(Translator::tr("Removed file '%1' for bunny '%2'", account).arg(name, QString(bunny->GetID())));
		}
		return new ApiManager::ApiError(Translator::tr("File '%1' not found for bunny %2", account).arg(name, QString(bunny->GetID())));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

PLUGIN_API_CALL(PluginSignature::Api_PluginSound)
{
	if(!hRequest.HasArg("action"))
		return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiManager::ApiMappedList(GetSettings("Sounds", QMap<QString, QVariant>()).toMap());
	}
	else if(action == "add")
	{
		QString file = "";
		if(hRequest.HasArg("url"))
		{
			file = hRequest.GetArg("url");

			if(file.left(7) != "http://")
				return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("url", GetName()));
		}
		else if (hRequest.HasArg("file"))
		{
			file = hRequest.GetArg("file");
		}
		else
		{
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' or '%2' for plugin %3", account).arg("url", "file", GetName()));
		}

		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString	name = hRequest.GetArg("name");

		if(!pluginSounds.contains(name))
		{
			pluginSounds.insert(name, file);
			SetSettings("Sounds", pluginSounds);
			return new ApiManager::ApiOk(Translator::tr("Added file '%1'", account).arg(file));
		}
		return new ApiManager::ApiError(Translator::tr("File '%1' already exists'", account).arg(name));
	}
	else if(action == "del")
	{
		if(!hRequest.HasArg("name"))
			return new ApiManager::ApiError(Translator::tr("Missing argument '%1' for plugin %2", account).arg("name", GetName()));

		QString name = hRequest.GetArg("name");
		int removed = pluginSounds.remove(name);
		if(removed > 0)
		{
			SetSettings("Sounds", pluginSounds);
			return new ApiManager::ApiOk(Translator::tr("Removed file '%1'", account).arg(name));
		}
		return new ApiManager::ApiError(Translator::tr("File '%1' not found", account).arg(name));
	}
	else
	{
		return new ApiManager::ApiError(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
	}
}

