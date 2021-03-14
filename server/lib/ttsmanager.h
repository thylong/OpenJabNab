#ifndef _TTSMANAGER_H_
#define _TTSMANAGER_H_

#include <QHash>
#include <QMap>
#include <QList>
#include "global.h"
#include "ttsanswer.h"
#include "ttsinterface.h"
#include "voice.h"
#include "apihandler.h"


class TTSInterface;
class QPluginLoader;
class OJN_EXPORT TTSManager : public ApiHandler<TTSManager>
{
public:
	enum OutputFormat { Format_Mp3 = 0, Format_Adp};
        static TTSManager & Instance();
	static void Init();
	static void Close();
	static TTSAnswer CreateSound(QString, QString, QString, OutputFormat output = Format_Mp3, bool overwrite = false);
	static TTSAnswer CreateSoundWithGenre(QString, QString, QString, Voice::VoiceGenre, OutputFormat output = Format_Mp3, bool overwrite = false);
	static QMap<QString, QVariant> GetVoiceList(QString, bool premium = false);
	static QString GetBestVoice(QString, QString);
	static Voice GetVoice(QString);
	TTSInterface * GetTTSByName(QString const& name) const;
	TTSInterface * GetTTSByNameOrNull(QString const& name) const;
	
	static void InitApiCalls();
	static QString trim(QString);

	static QStringList split(QString, int);
	static QStringList splitForVoice(QString, QString);

    	static QString convertToAdp(QString, bool overwrite = false, bool fullPath = false, bool returnFull = false);
protected:
	static QDir ttsFolder;
	static QString ttsHTTPUrl;
private:
	static int findNextCut(QString, int);
	TTSManager();
        void LoadTTSs();
        void UnloadTTSs();
        bool LoadTTS(QString const&); 
        bool UnloadTTS(QString const&); 
        bool ReloadTTS(QString const&); 
        QDir ttsDir;
        QList<TTSInterface *> listOfTTSs;
        QMap<TTSInterface *, QString> listOfTTSsFileName;
        QMap<TTSInterface *, QPluginLoader *> listOfTTSsLoader;
        QHash<QString, TTSInterface *> listOfTTSsByName;
        QHash<QString, TTSInterface *> listOfTTSsByFileName;
	static bool ApproxLanguage(QString, QString);
	static TTSAnswer createSound(TTSInterface *, TTSAnswer, QString, QString, OutputFormat output = Format_Mp3, bool overwrite = false);

	API_CALL(Api_TTS);
	API_CALL(Api_Voices);
};

inline void TTSManager::Init()
{
        Instance().LoadTTSs();
	InitApiCalls();
}

inline void TTSManager::Close()
{
        Instance().UnloadTTSs();
}

inline TTSInterface * TTSManager::GetTTSByName(QString const& name) const
{
	if(listOfTTSsByName.contains(name))
	{
		return listOfTTSsByName.value(name);
	}
	return listOfTTSsByName.value("google");
}

inline TTSInterface * TTSManager::GetTTSByNameOrNull(QString const& name) const
{
	if(listOfTTSsByName.contains(name))
	{
		return listOfTTSsByName.value(name);
	}
	return NULL;
}


#endif
