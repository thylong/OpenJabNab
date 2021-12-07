#ifndef _VOICESTRUCTURE_H_
#define _VOICESTRUCTURE_H_

typedef struct {
	enum VoiceGenre { Unknow = 0, Man, Woman };
	QString name;
	QString subname;
	QString label;
	QString language;
	QString sublanguage;
	QString language_id;
	int genre;
	int limit;
	QString toString() {
		return label.length() > 0 ? label : name;
	};
} Voice;

#endif
