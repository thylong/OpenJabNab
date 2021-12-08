#ifndef _NABAZTAGMANAGER_H_
#define _NABAZTAGMANAGER_H_

#include <QList>
#include <QHash>
#include <QVector>
#include <QTcpSocket>
#include "bunny.h"
#include "global.h"
#include "apihandler.h"


class Account;
class HTTPRequest;
class PluginInterface;
class OJN_EXPORT NabaztagManager
  : public ApiHandler<NabaztagManager>
{
  friend class PluginAuth;
  friend class ApiManager;
  friend class PluginManager;

public:
  enum Color
  {
    ColorOff        =  0,
    ColorRed        =  1,
    ColorGreen      =  2,
    ColorYellow     =  3,
    ColorBlue       =  4,
    ColorPurple     =  5,
    ColorCyan       =  6,
    ColorWhite      =  7,
    ColorPaleWhite  =  8,
    ColorPaleRed    =  9,
    ColorPaleGreen  = 10,
    ColorPaleYellow = 11,
    ColorPaleBlue   = 12,
    ColorPalePruple = 13,
    ColorPaleCyan   = 14,
    ColorOrange     = 15
  };

  enum Services
  {
    ServiceNone         = 0,
    ServiceWeather      = 1,  // 0..5
    ServiceStockMarket  = 2,  // 0..6
    ServiceTraffic      = 3,  // 0..6
    ServiceEmail        = 6,  // 0..2
    ServiceAirQuality   = 7,  // 0..2
    ColorBreathing      = 9,  // 0..15, see Color Enum
    ServiceSleep        = 13, // 0 for Idle, 2 for Sleep
    ServiceTaiChi       = 14, // 0..0xFF
    LeftEar             = 16, // 0..16
    RightEar            = 17, // 0..16
    Nose                = 18  // 0..16
  };

  static NabaztagManager & Instance();

  void PluginStateChanged(PluginInterface *);
  static void Init();
  static void Close();

  void handlePing(const HTTPRequest&, QTcpSocket *);

  static void UpdateStatus();

  // API
  void InitApiCalls();

  bool loadAMsgBytecode(const QString& file);
  QByteArray getAMsgForADP(const size_t trame, QString nadp_file);
  static QByteArray readFile(QString);
  static QByteArray getSignature();

private:
  NabaztagManager();
  static QByteArray buildPacket(const QList<QByteArray>& list);
  static QByteArray buildPacket(const QByteArray& msg);
  static QByteArray setDelay(int);
  static QByteArray setServiceData(Bunny *);
  static int checksum(QByteArray);
  static QByteArray loadBytecode(QString, Bunny *);
  static QByteArray encodeHexInt(int, int);

  QMap<Bunny *, QString> byteCodes;
  QMap<Bunny *, QStringList> soundToSend;
  QMap<Bunny *, QDateTime> lastPing;
  QMap<Bunny *, QDateTime> previousPing;
  QByteArray defaultBytecode;
  QByteArray _amsgBytecode;
};

inline void NabaztagManager::Init()
{
  Instance().InitApiCalls();
}

#endif
