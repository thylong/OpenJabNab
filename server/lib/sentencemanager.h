#ifndef _SENTENCEMANAGER_H_
#define _SENTENCEMANAGER_H_

#include <QHash>
#include <QString>
#include <QStringList>
#include <QSettings>
#include "global.h"
#include "apihandler.h"


class OJN_EXPORT SentenceManager : public ApiHandler<SentenceManager>
{
	friend class OpenJabNab;
public:
	static SentenceManager & Instance();
	void SaveSentences();

	static QString const GetSentence(QString, QString, QString);
	static QString const GetSentence(QString, QString);
	static QString const GetSentence(QString, int, QString);
	static QString const GetSentence(QString, QString, bool, QString);
	static QString const GetSentence(QString, QString, bool);
	static QString const GetSentence(QString, int, QString, bool);
	static QStringList const GetSentences(QString, QString);
	static QString InsertData(QString, QHash<QString, QString> );

protected:
	static inline void Init();
	virtual ~SentenceManager();
	static inline void Close();

private:
	SentenceManager();
	void LoadSentences();
	static void InitApiCalls();
	QHash<QString, QHash<QString, QStringList> > sentencesPool;
	
	bool AddSentence(QString, QString, QString);
	bool RemoveSentence(QString, QString, QString);

	QString const getSentence(QString, QString, QString);
	QString const getSentence(QString, QString);
	QStringList const getSentences(QString, QString);

	// Files settings
	QVariant GetSettings(QString const& key, QVariant const& defaultValue = QVariant()) const;
	void SetSettings(QString const& key, QVariant const& value);
	void RemoveSettings(QString const& key);
	QSettings * settings;

	// API
	API_CALL(Api_Language);
	API_CALL(Api_Type);
	API_CALL(Api_Sentence);
};

inline void SentenceManager::Init()
{
	Instance().LoadSentences();
	InitApiCalls();
}

inline void SentenceManager::Close()
{
	Instance().SaveSentences();
}

#endif
