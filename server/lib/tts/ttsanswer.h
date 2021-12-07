#ifndef _TTSANSWER_H_
#define _TTSANSWER_H_

typedef struct {
	QString text;
	QString askedLanguage;
	QString returnedLanguage;
	QString askedVoice;
	QString returnedVoice;
	QString filePath;
	QString file;
	bool overwrite;
	bool cache;
} TTSAnswer;

#endif
