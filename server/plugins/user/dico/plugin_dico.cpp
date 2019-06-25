#include <QDateTime>
#include <QMapIterator>
#include "bunny.h"
#include "bunnymanager.h"
#include "cron.h"
#include "messagepacket.h"
#include "plugin_dico.h"
#include "ttsmanager.h"
#include "translator.h"
#include "log.h"

PluginDico::PluginDico():PluginInterface("dico", "Dictionary", BunnyV2Plugin | PremiumPlugin | VoicePlugin | ApiPlugin)
{
}

QString PluginDico::OnApiSpell(Bunny *b, QVariant v)
{
	QString str = v.value<QString>();
	spellWord(b, str);
	return Translator::tr("Spelling '%1'").arg(cleanWords(str, b).join(" "));
}

QStringList PluginDico::cleanWords(QString str, Bunny * b)
{
	QStringList words = cleanStringPertinence(str).split(" ");
	QStringList removed = cleanStringPertinence(Translator::tr("to spell,spell,to write,write,writing", b)).split(",");
	foreach(QString remove, removed)
	{
		words.removeAll(remove.trimmed());
	}

	return words;
}

PluginDico::~PluginDico()
{
}

bool PluginDico::spellWord(Bunny * b, QString str)
{
	QStringList files;
	QStringList words = cleanWords(str, b);

	if(words.length())
	{
		for(int j=0; j< words.length(); j++)
		{
			QString word = words.at(j);

			QStringList letters;
			for(int i=0; i< word.length(); i++)
			{
				letters.append(word.at(i));
			}
			QString string = letters.join(", ");

			TTSAnswer file = TTSManager::CreateSound(string, b->GetVoice(), b->GetLanguage());
			TTSLog(b->GetID(), GetName(), file);
			files.append(file.file);

			if(j+1 < words.length())
			{
				TTSAnswer file = TTSManager::CreateSound(Translator::tr("space", b), b->GetVoice(), b->GetLanguage());
				TTSLog(b->GetID(), GetName(), file);
				files.append(file.file);
			}
		}

		QByteArray message;
		foreach(QString file, files)
		{
			message += "MU " + file.toLatin1() + "\nMW\n";
		}
		if(b->IsConnected())
		{
			b->SendPacket(MessagePacket(message), GetName());
		}
		return true;
	}
	return false;
}

bool PluginDico::OnVoiceCommand(Bunny * b, QString const& command, QStringList const&)
{
	if (getPertinence(Translator::tr("to spell,spell,to write,write,writing", b), command))
	{
		spellWord(b, command);
		return true;
	}
	return false;
}

/*******
 * API *
 *******/

void PluginDico::InitApiCalls()
{
}
