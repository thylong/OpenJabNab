#include <QCoreApplication>
#include <QString>
#include <QDebug>
#include <QDir>
#include <QRandomGenerator>
#include "sentencemanager.h"

#include "log.h"
#include "settings.h"
#include "translator.h"

SentenceManager::SentenceManager()
{
	QDir dir = QDir(GlobalSettings::GetConfigDir());
	settings = new QSettings(dir.absoluteFilePath("sentences.ini"), QSettings::IniFormat);
}

SentenceManager & SentenceManager::Instance()
{
  static SentenceManager p;
  return p;
}

void SentenceManager::SaveSentences()
{
	settings->clear();

	QStringList types;
	QStringList languages;
	QHashIterator<QString, QHash<QString, QStringList> > i(sentencesPool);
	while (i.hasNext()) {
		i.next();
		QString type = i.key();
		QHashIterator<QString, QStringList> sentences = i.value();
		QHashIterator<QString, QStringList> j(sentences);
		int count = 0;
		while (j.hasNext()) {
			j.next();
			QString language = j.key();
			QStringList list = j.value();

			if(list.count())
			{
				count++;
				languages << language;
				settings->setValue(QString("%1/%2").arg(type, language), list);
			}
		}
		if(count > 0)
		{
			types << type;
		}
		languages.removeDuplicates();
	}
	types.removeDuplicates();

	settings->setValue("List/Types", types);
	settings->setValue("List/Languages", languages);
	settings->sync();
}

QString SentenceManager::InsertData(QString string, QHash<QString, QString> replacements)
{
	QHashIterator<QString, QString> i(replacements);
	while (i.hasNext()) {
		i.next();
		QString key = i.key();
		QString value = i.value();
		string = string.replace(key, value);
	}
	return string;
}

SentenceManager::~SentenceManager()
{
	delete settings;
}

void SentenceManager::LoadSentences()
{
	sentencesPool.clear();
	QStringList languages = settings->value("List/Languages", QStringList()).toStringList();
	foreach(QString type, settings->value("List/Types", QStringList()).toStringList())
	{
		QHash<QString, QStringList> sentences;
		foreach(QString language, languages)
		{
			sentences.insert(language, settings->value(QString("%1/%2").arg(type, language), QStringList()).toStringList());
		}
		sentencesPool.insert(type, sentences);
	}
	//listOfSentencesByName.insert(a->GetLogin(), a);
	//LogInfo(QString("Total of sentences: %1").arg(listOfSentences.count()));
}

QString const SentenceManager::GetSentence(QString type, int number, QString language, bool approx)
{
	QString wishedType = type;
	QString lng = "";

	wishedType.replace("XXX", QString::number(number));
	QString string = Instance().getSentence(wishedType, language);
	if(!string.length())
	{
		string = Instance().getSentence(type, language);
	}
	if(!string.length() && approx)
	{
		lng = GlobalSettings::Get("TTSApprox/" + language, QString()).toString();
		if(lng.length())
		{
			string = Instance().getSentence(wishedType, lng);
			if(!string.length())
			{
				string = Instance().getSentence(type, lng);
			}
		}
	}
	string.replace("XXX", QString::number(number));

	if(!string.length())
	{
		LogSentence(QString("No sentence for key '%1' nor '%2'").arg(type, wishedType), language, lng);
	}
	return string;
}

QString const SentenceManager::GetSentence(QString type, QString language, bool approx)
{
	return SentenceManager::GetSentence(type, language, approx, QString());
}

QString const SentenceManager::GetSentence(QString type, QString language, bool approx, QString defaultString)
{
	QString string = Instance().getSentence(type, language, defaultString);
	QString lng = "";
	if(!string.length() && approx)
	{
		lng = GlobalSettings::Get("TTSApprox/" + language, QString()).toString();
		if(lng.length())
		{
			string = Instance().getSentence(type, lng);
		}
	}
	if(!string.length())
	{
		LogSentence(QString("No sentence for key '%1'").arg(type), language, lng);
	}
	return string;
}

QString const SentenceManager::GetSentence(QString type, int number, QString language)
{
	return SentenceManager::GetSentence(type, number, language, true);
}

QString const SentenceManager::GetSentence(QString type, QString language)
{
	return SentenceManager::GetSentence(type, language, true);
}

QStringList const SentenceManager::GetSentences(QString type, QString language)
{
	return Instance().getSentences(type, language);
}

QString const SentenceManager::getSentence(QString type, QString language)
{
	return getSentence(type, language, QString());
}

QString const SentenceManager::getSentence(QString type, QString language, QString defaultString)
{
	QStringList list = GetSentences(type, language);
	if(list.count())
	{
		return list.at(QRandomGenerator::global()->generate() % list.count());
	}
	return defaultString;
}

QStringList const SentenceManager::getSentences(QString type, QString language)
{
	if(sentencesPool.contains(type))
	{
		if(sentencesPool.value(type).contains(language))
		{
			return sentencesPool.value(type).value(language);
		}
	}
	return QStringList();
}

bool SentenceManager::AddSentence(QString type, QString language, QString sentence)
{
	QStringList list = GetSentences(type, language);
	if(!list.contains(sentence))
	{
		QHash<QString, QStringList> sentences = sentencesPool.value(type, QHash<QString, QStringList>());
		list.append(sentence);
		sentences.insert(language, list);
		sentencesPool.insert(type, sentences);
		return true;
	}
	return false;
}

bool SentenceManager::RemoveSentence(QString type, QString language, QString sentence)
{
	QStringList list = GetSentences(type, language);
	if(list.contains(sentence))
	{
		QHash<QString, QStringList> sentences = sentencesPool.value(type, QHash<QString, QStringList>());
		list.removeAll(sentence);
		sentences.insert(language, list);
		sentencesPool.insert(type, sentences);
		return true;
	}
	return false;
}

// Settings
inline QVariant SentenceManager::GetSettings(QString const& key, QVariant const& defaultValue) const
{
	return settings->value(key, defaultValue);
}

inline void SentenceManager::SetSettings(QString const& key, QVariant const& value)
{
	settings->setValue(key, value);
	settings->sync();
}

inline void SentenceManager::RemoveSettings(QString const& key)
{
	settings->remove(key);
	settings->sync();
}

/*******
 * API *
 *******/

void SentenceManager::InitApiCalls()
{
	DECLARE_API_CALL("language()", &SentenceManager::Api_Language);
	DECLARE_API_CALL("type()", &SentenceManager::Api_Type);
	DECLARE_API_CALL("sentence()", &SentenceManager::Api_Sentence);
}

API_CALL(SentenceManager::Api_Language)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::List(settings->value("List/Languages", QStringList()).toStringList());
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(SentenceManager::Api_Type)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(action == "list")
	{
		return new ApiAnswers::List(settings->value("List/Types", QStringList()).toStringList());
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}

API_CALL(SentenceManager::Api_Sentence)
{
	if(!hRequest.HasArg("action"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("action"));

	QString action = hRequest.GetArg("action");

	if(!hRequest.HasArg("type"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("type"));

	QString type = hRequest.GetArg("type");

	if(!hRequest.HasArg("language"))
		return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("language"));

	QString language = hRequest.GetArg("language");

	if(action == "list")
	{
		return new ApiAnswers::List(GetSentences(type, language));
	}
	else if(action == "save")
	{
		SaveSentences();
		return new ApiAnswers::Ok(Translator::tr("Sentences saved", account));
	}
	else if(action == "get")
	{
		if(hRequest.HasArg("number"))
		{
			int number = hRequest.GetArg("number").toInt();
			QString string = SentenceManager::GetSentence(type, number, language);
			if(string.length())
			{
				return new ApiAnswers::String(string);
			}
			return new ApiAnswers::Error(Translator::tr("No sentence for '%1' (%3) in '%2'", account).arg(type, language, QString::number(number)));
		}
		else
		{
			QString string = SentenceManager::GetSentence(type, language);
			if(string.length())
			{
				return new ApiAnswers::String(string);
			}
			return new ApiAnswers::Error(Translator::tr("No sentence for '%1' in '%2'", account).arg(type, language));
		}
	}
	else if(action == "add")
	{
		if(!hRequest.HasArg("sentence"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("sentence"));

		QString sentence = hRequest.GetArg("sentence");
		if(AddSentence(type, language, sentence))
		{
			return new ApiAnswers::Ok(Translator::tr("Sentences added to list", account));
		}
		return new ApiAnswers::Error(Translator::tr("Sentence already in list", account));
	}
	else if(action == "remove")
	{
		if(!hRequest.HasArg("sentence"))
			return new ApiAnswers::Error(Translator::tr("Missing argument '%1'", account).arg("sentence"));

		QString sentence = hRequest.GetArg("sentence");
		QStringList list = GetSentences(type, language);
		if(RemoveSentence(type, language, sentence))
		{
			return new ApiAnswers::Ok(Translator::tr("Sentences removed from list", account));
		}
		return new ApiAnswers::Error(Translator::tr("Sentence is not in list", account));
	}
	else
	{
		return new ApiAnswers::Error(Translator::tr("Bad argument '%1'", account).arg("action"));
	}
}
