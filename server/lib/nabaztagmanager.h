#ifndef _NABAZTAGMANAGER_H_
#define _NABAZTAGMANAGER_H_

#include <QList>
#include <QHash>
#include <QVector>
#include <QTcpSocket>
#include "bunny.h"
#include "global.h"
#include "apihandler.h"
#include "apimanager.h"

class Account;
class HTTPRequest;
class PluginInterface;
class OJN_EXPORT NabaztagManager : public ApiHandler<NabaztagManager>
{
	friend class PluginAuth;
	friend class ApiManager;
	friend class PluginManager;

public:
	enum Color { ColorOff = 0, ColorRed = 1, ColorGreen = 2, ColorYellow = 3, ColorBlue = 4, ColorPurple = 5, ColorCyan = 6, ColorWhite = 7, ColorPaleWhite = 8, ColorPaleRed = 9, ColorPaleGreen = 0xa, ColorPaleYellow = 0xb, ColorPaleBlue = 0xc, ColorPalePruple = 0xd, ColorPaleCyan = 0xe, ColorOrange = 0xf};
	enum Services { ServiceNone = 0, ColorBreathing = 9, LeftEar = 16, RightEar = 17, Nose = 18 };
	static NabaztagManager & Instance();

	static void PluginStateChanged(PluginInterface *);
	static void Init();
	static void Close();

	static void handlePing(HTTPRequest, QTcpSocket *);

	static QList<QByteArray> GetConnectedNabaztagsList(void);
	static void UpdateStatus();

	// API
	static void InitApiCalls();

private:
	NabaztagManager();
	static QByteArray buildPacket(QList<QByteArray>);
	static QByteArray buildPacket(QByteArray);
	static QByteArray readFile(QString);
	static QByteArray getAdpBytecode();
	static QByteArray getMidBytecode();
	static QByteArray setDelay(int);
	static QByteArray setServiceData(Bunny *);
	static QByteArray getSignature();
	static int checksum(QByteArray);
	static QByteArray loadBytecode(QString, Bunny *);
	static QByteArray insertAdpFile(QString, int);
	static QByteArray insertTest();
	static QByteArray encodeHexInt(int, int);

	static QMap<Bunny *, QString> byteCodes;
	static QMap<Bunny *, QStringList> soundToSend;
	static QMap<Bunny *, QDateTime> lastPing;
	static QMap<Bunny *, QDateTime> previousPing;
	static QByteArray defaultBytecode;
};

inline void NabaztagManager::Init()
{
	InitApiCalls();
}

#endif
