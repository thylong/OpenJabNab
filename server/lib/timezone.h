#ifndef _TIMEZONE_H_
#define _TIMEZONE_H_

#include <QByteArray>
#include <QHash>
#include <QString>
#include <QTimer>
#include <QDir>
#include <QVariant>
#include "apihandler.h"
#include "apimanager.h"
#include "global.h"
#include "packet.h"
#include "plugininterface.h"

class XmppHandler;
class OJN_EXPORT Timezone : public QObject, public ApiHandler<Timezone>
{
	friend class TimezoneManager;
	Q_OBJECT
public:
	virtual ~Timezone();

	static void Init() { InitApiCalls(); }

	QString GetAlias() const;
	QString GetZone() const;
	QString GetArea() const;
	QString GetLocation() const;
	QString GetStdCode() const;
	QString GetStdName() const;
	QString GetDstCode() const;
	QString GetDstName() const;
	float GetStdOffset() const;
	float GetDstOffset() const;
	void SetArea(QString);
	void SetLocation(QString);
	void SetStdCode(QString);
	void SetStdName(QString);
	void SetDstCode(QString);
	void SetDstName(QString);
	void SetStdOffset(float);
	void SetDstOffset(float);

	float getOffsetAt(QDateTime);
	float getCurrentOffset();
	bool dstActivatedAt(QDateTime);
	bool dstActivated();
	QStringList explodeDst(QString);

	QStringList GetDstList();
	void AddDst(QString, QString);
	void RemoveDst(QString, QString);

	QStringList GetAliases();
	void AddAlias(QString);
	void RemoveAlias(QString);

	// API
	static void InitApiCalls();

private slots:
	void SaveConfig();

private:
	Timezone(QString const&, const QDir& tzDir);
	Timezone(QString const&, QString const&, const QDir& tzDir);
	void Initialize(QString const&, const QDir& tzDir);
	QString configFileName;
	void LoadConfig();

	// API
        API_CALL(Api_getTimezone);
        API_CALL(Api_setTimezone);
        API_CALL(Api_getDstList);
        API_CALL(Api_addDst);
        API_CALL(Api_removeDst);
        API_CALL(Api_Alias);

	QString area;
	QString location;
	QString stdCode;
	QString stdName;
	QString dstCode;
	QString dstName;
	float stdOffset;
	float dstOffset;
	QStringList dstList;
	QStringList aliases;

	QString alias;
};

inline QString Timezone::GetAlias() const
{
	return alias;
}

inline QString Timezone::GetZone() const
{
	return area + "/" + location;
}

inline QString Timezone::GetArea() const
{
	return area;
}

inline QString Timezone::GetLocation() const
{
	return location;
}

inline QString Timezone::GetStdCode() const
{
	return stdCode;
}

inline QString Timezone::GetStdName() const
{
	return stdName;
}

inline QString Timezone::GetDstCode() const
{
	return dstCode;
}

inline QString Timezone::GetDstName() const
{
	return dstName;
}

inline float Timezone::GetStdOffset() const
{
	return stdOffset;
}

inline float Timezone::GetDstOffset() const
{
	return dstOffset;
}

inline void Timezone::SetArea(QString a)
{
	area = a;
}

inline void Timezone::SetLocation(QString l)
{
	location = l;
}

inline void Timezone::SetStdCode(QString c)
{
	stdCode = c;
}

inline void Timezone::SetStdName(QString n)
{
	stdName = n;
}

inline void Timezone::SetDstCode(QString c)
{
	dstCode = c;
}

inline void Timezone::SetDstName(QString n)
{
	dstName = n;
}

inline void Timezone::SetStdOffset(float o)
{
	stdOffset = o;
}

inline void Timezone::SetDstOffset(float o)
{
	dstOffset = o;
}

inline QStringList Timezone::GetAliases()
{
	return aliases;
}

inline void Timezone::AddAlias(QString a)
{
	if(!aliases.contains(a))
	{
		aliases.append(a);
		aliases.sort();
		aliases.removeDuplicates();
	}
}

inline void Timezone::RemoveAlias(QString a)
{
	if(aliases.contains(a))
	{
		aliases.removeAll(a);
		aliases.sort();
		aliases.removeDuplicates();
	}
}

inline QStringList Timezone::GetDstList()
{
	return dstList;
}

inline void Timezone::AddDst(QString s, QString e)
{
	if(!dstList.contains(s + "|" + e))
	{
		dstList.append(s + "|" + e);
	}
}

inline void Timezone::RemoveDst(QString s, QString e)
{
	if(dstList.contains(s + "|" + e))
	{
		dstList.removeAll(s + "|" + e);
	}
}

Q_DECLARE_METATYPE(Timezone*)
namespace QVariantHelper
{
    inline Timezone* ToTimezonePtr(QVariant v) { return v.value<Timezone*>(); }
};
#endif
