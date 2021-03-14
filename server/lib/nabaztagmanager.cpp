#include <QFile>
#include <QHostAddress>
#include <memory>
#include <QMetaObject>
#include <QMetaMethod>
#include "QsLog.h"
#include "account.h"
#include "ambientpacket.h"
#include "nabaztagmanager.h"
#include "pluginmanager.h"
#include "bunny.h"
#include "httprequest.h"
//#include "netdump.h"
#include "settings.h"
#include "bunnymanager.h"
#include "translator.h"
#include "ttsmanager.h"

NabaztagManager::NabaztagManager()
{
  lastPing.clear();
  previousPing.clear();
  byteCodes.clear();
  soundToSend.clear();

  QString nabPath = GlobalSettings::GetString("Nabaztag/NabFilesDir",
                      QCoreApplication::applicationDirPath().append("/nabfiles/"));
  {
    QString nabFile = GlobalSettings::GetString("Nabaztag/DefaultNabfile","default_violet");
    QString fileName = QDir(nabPath).absoluteFilePath(nabFile+".nab");
    defaultBytecode = readFile(fileName);
  }
  {
    // FIXME Changing the template REQUIRES changing the values in the 
    // "Fix file offset" section of getAMsgForADP()
    QString nadpFile = "amsg_tpl.nadp";
    QString fileName = QDir(nabPath).absoluteFilePath(nadpFile);
    loadAMsgBytecode(fileName);
  }
}

bool NabaztagManager::loadAMsgBytecode(const QString& file)
{
  //qDebug() << "NabaztagManager::loadAMsgByteCode, filename: " << file;
  _amsgBytecode = readFile(file);
  //qDebug() << "Bytecode: " << _amsgBytecode;
  return !_amsgBytecode.isEmpty();
}

QByteArray NabaztagManager::getAMsgForADP(const size_t trame, QString filename)
{
  if(filename.startsWith("broadcast"))
  {
    filename.replace("broadcast/ojn_local/", GlobalSettings::Get("Config/RealHttpRoot", QString()).toString());
  }
  const QByteArray& payload = readFile(filename);
  //qDebug() << "AMsg length" << QByteArray::fromHex(encodeHexInt(_amsgBytecode.length(),6));
  //qDebug() << "Payload length" << QByteArray::fromHex(encodeHexInt(payload.length(),6));
  //const auto& sz = _amsgBytecode.length() + payload.length();
  //qDebug() << "Sz            " << QByteArray::fromHex(encodeHexInt(sz,6));
  QByteArray r  = QByteArray::fromHex("05") 
                + QByteArray::fromHex(encodeHexInt( 0x000000, 6 ))
                + "amber"
                + QByteArray::fromHex(encodeHexInt( trame, 8 ))
                + QByteArray::fromHex("01")
                //+ QByteArray::fromHex(encodeHexInt( _amsgBytecode.length(), 8 ))
                + _amsgBytecode
                + payload
                + QByteArray::fromHex("00") // Will fix later
                + "mind";
  // Fix file offset
  const auto foff_off = 0x1E3+1+6+5+8+1-1-6;
  const auto foff = 0x000000DF + payload.length();
  r.replace(foff_off,4,QByteArray::fromHex(encodeHexInt(foff,8)));
  // Fix Size
  const auto sz = r.length()-4;
  const auto sz_off=1;
  r.replace(sz_off,3,QByteArray::fromHex(encodeHexInt(sz,6)));
  qDebug() << "Sz       :" << QByteArray::fromHex(encodeHexInt(sz,6));
  //Fix Checksum
  const auto cs_off=r.length() - 5; // 
  qDebug() << "Checksum :" << QByteArray::fromHex(encodeHexInt( checksum(r), 2));
  r.replace(cs_off,1,QByteArray::fromHex(encodeHexInt( checksum(r), 2)));
  return buildPacket(r);
}

NabaztagManager & NabaztagManager::Instance()
{
  static NabaztagManager b;
  return b;
}

void NabaztagManager::UpdateStatus()
{
  QMapIterator<Bunny *, QDateTime> i(Instance().lastPing);
  while (i.hasNext())
  {
    i.next();
    Bunny * bunny = i.key();
    QDateTime last = i.value();
    QDateTime now = QDateTime::currentDateTime();
    if(Instance().previousPing.contains(bunny))
    {
      if(last.addSecs(5 * 60) < now)
      {
        LogDebug("Nabaztag " + bunny->GetBunnyName() + " stops pinging the server");
        bunny->OnNoPing();
        Instance().previousPing.remove(bunny);
        Instance().lastPing.remove(bunny);
      }
    }
    else
    {
      if(last.addSecs(5 * 60) < now)
      {
        LogDebug("Nabaztag " + bunny->GetBunnyName() + " stops pinging the server");
        bunny->OnNoPing();
        Instance().lastPing.remove(bunny);
      }
      else
      {
        LogDebug("Nabaztag " + bunny->GetBunnyName() + " starts pinging the server");
        bunny->OnNewPing();
      }
    }
    Instance().previousPing.insert(bunny, last);
  }
}

QByteArray NabaztagManager::setServiceData(Bunny * b)
{
  // Type "Message"
  QByteArray ret = QByteArray::fromHex("04");

  // Trame id
  QByteArray ret2 = QByteArray::fromHex(encodeHexInt( 1, 8 ));

  // Services x8
  if(true)
  {
    QByteArray ret3 = QByteArray();
    QMap<int, int> services = b->GetServices();
    QMapIterator<int, int> i(services);
    while (i.hasNext())
    {
      i.next();
      if(i.key() < 16)
      {
        ret3 += QByteArray::fromHex(encodeHexInt( i.key(), 2 )) + QByteArray::fromHex(encodeHexInt( i.value(), 2 ));
      }
    }
    ret2 += ret3.leftJustified(16, 0);
  }
  else
  {
    ret2 += QByteArray::fromHex(encodeHexInt( 9, 2 )) + QByteArray::fromHex(encodeHexInt( b->GetService(9), 2 ));
    for(int i=0; i<7; i++)
      ret2 += QByteArray::fromHex(encodeHexInt( 0, 4 ));
  }

  // Left ear
  ret2 += QByteArray::fromHex(encodeHexInt( b->GetService(LeftEar), 2 ));
  // Right ear
  ret2 += QByteArray::fromHex(encodeHexInt( b->GetService(RightEar), 2 ));
  // Nose
  ret2 += QByteArray::fromHex(encodeHexInt( b->GetService(Nose), 2 ));

  // Size on 6
  ret += QByteArray::fromHex(encodeHexInt(ret2.length(), 6));
  // Services, ears and nose (previously defined)
  ret += ret2;
  //LogDebug("Nabaztag " + b->GetBunnyName() + " set Services: " + QString(ret.toHex()));
  return ret;
}

QByteArray NabaztagManager::setDelay(int delay)
{
  delay = delay / 10;
  if(delay < 1)
  {
    delay = 3;
  }
  QByteArray ret = QByteArray::fromHex("03");
  ret += QByteArray::fromHex("000001");
  ret += QByteArray::fromHex(encodeHexInt( delay, 2 ));
  return ret;
}

QByteArray NabaztagManager::readFile(QString filename)
{
  QFile bootcodeFile( filename );
  if(bootcodeFile.open(QFile::ReadOnly))
  {
    LogDebug("Nabaztag: Reading bytecode from "+filename);
    return bootcodeFile.readAll();
  }
  LogDebug("Nabaztag: Could not find "+filename+" to read bytecode from");
  return QByteArray();
}

QByteArray NabaztagManager::getMidBytecode()
{
  return QByteArray::fromHex("00020500ac00417e02ac0062ac006b0000ac006eac004f7e05c50085009d002801FFc4017e05b80085009e00289c003501ffa8010404a7458004a00047ad00050514ac00417d0500000514ac00417d05ad82000101ae00ae10adb0b2ad01ffb601af007e01c10085009e00747e01b0ad");
}

QByteArray NabaztagManager::getAdpBytecode()
{
  return QByteArray::fromHex("00020500ac00417e02ac0062ac006b0000ac006eac004f7e05c50085009d002801FFc4017e05b80085009e00289c003501ffa8010404a7458004a00047ad00050514ac00417d0500000514ac00417d05ad82000101ae00ae10adb0b2ad01ffb601b1007e01c10085009e00747e01b2ad");
}

QByteArray NabaztagManager::buildPacket(const QList<QByteArray>& list)
{
  QByteArray ret = QByteArray::fromHex("7F");
  foreach(QByteArray message, list)
  {
    ret += message;
  }
  ret += QByteArray::fromHex("FF") + QByteArray::fromHex("0A") + NabaztagManager::getSignature();
  return ret;
}

QByteArray NabaztagManager::buildPacket(const QByteArray& message)
{
  QByteArray ret = QByteArray::fromHex("7F");
  ret += message;
  ret += QByteArray::fromHex("FF") + QByteArray::fromHex("0A") + NabaztagManager::getSignature();
  return ret;
}

QByteArray NabaztagManager::insertAdpFile(QString filename, int trame)
{
  if(filename.startsWith("broadcast"))
  {
    filename.replace("broadcast/ojn_local/", GlobalSettings::Get("Config/RealHttpRoot", QString()).toString());
  }
  QByteArray file = readFile(filename);
  // Header
  //QByteArray ret = QByteArray::fromHex("7F");
  // Bytecode to play adp file
  QByteArray ret = QByteArray::fromHex("05") + QByteArray::fromHex(encodeHexInt( file.length() + 139, 6 ));
  //LogDebug(QString::number( file.length()));
  //LogDebug(QString(file));
  ret += "amber";
  // Trame ID, transition flag
  ret += QByteArray::fromHex(encodeHexInt( trame, 8 )) + QByteArray::fromHex("01");
  // Bytecode
  ret += QByteArray::fromHex(encodeHexInt( getAdpBytecode().length(), 8 )) + getAdpBytecode();
  // Nbr music
  if(file.length() > 0)
  {
    ret += QByteArray::fromHex(encodeHexInt( 1, 8 ));
    ret += QByteArray::fromHex(encodeHexInt( file.length(), 8 ));
    ret += file;
  }
  else
  {
    ret += QByteArray::fromHex(encodeHexInt( 0, 8 ));
    //ret += QByteArray::fromHex(encodeHexInt( 0, 8 ));
  }
  ret += QByteArray::fromHex(encodeHexInt( checksum(ret + "mind"), 2)) + "mind";
  return ret;
}

QByteArray NabaztagManager::getSignature()
{
  QString ret("http://");
  ret += GlobalSettings::GetString("OpenJabNabServers/PingServer");
  return ret.toUtf8();
}

int NabaztagManager::checksum(QByteArray data)
{
  int sum = 0;
  for(size_t i = 0; i < data.size(); i++)
  {
    sum += ((uchar)data.at(i) ) ;
    if(sum >= 256)
    {
      sum -= 256;
    }
  }
  return 255 - sum;
}

QByteArray NabaztagManager::loadBytecode(QString filename, Bunny * n)
{
  Instance().byteCodes.insert(n, filename);
  if(filename == GlobalSettings::GetString("Nabaztag/DefaultNabfile","default_violet"))
  {
    return Instance().defaultBytecode;
  }
  else
  {
    QString nabPath = GlobalSettings::GetString("Nabaztag/NabFilesDir",
                        QCoreApplication::applicationDirPath().append("/nabfiles/"));
    QString fileName = QDir(nabPath).absoluteFilePath(filename + ".nab" );
    LogDebug("Loading bytecode " + fileName);
    return readFile(fileName) + QByteArray::fromHex("0A");
  }
}

QByteArray NabaztagManager::encodeHexInt(int nbr, int len)
{
  return QString::number(nbr, 16).rightJustified(len, '0').toLatin1();
}

void NabaztagManager::handlePing(const HTTPRequest& request, QTcpSocket * s)
{
  QString sn = request.GetArg("sn");      // Nabaztag MAC Address
  //size_t  v  = request.GetArg("v");     // Firmware version
  //size_t ex  = request.GetArg("ex");    // Unknown, always 00
  //size_t st  = request.GetArg("st");    // 0 for first request, 1 after
  //size_t sd  = request.GetArg("sd");    // Action type:
                                          // 0001 Double click
                                          // 0002 Event finished (eg: audio playback)
                                          // 0003 Single click
                                          // 0004 Long click
                                          // 0005 Playback stopped
                                          // 01FF Unknown
                                          // 8XXX or 9XXX Ears movement
  //size_t ts  = request.GetArg("ts");    // Unknown
  //size_t tc  = request.GetArg("tc");    // Unknown. Services ? Trame counter ?
  //size_t tn  = request.GetArg("tn");    // Unknown 

  Bunny * n = BunnyManager::GetBunny(sn.toLatin1());
  bool log = n->GetGlobalSetting("DumpLog",false).toBool();
  if(n != NULL)
  {
    bool needBytecode = false;
    QString previousBytecode = Instance().byteCodes.value(n);
    n->UpdateServices();
    QString bytecode = n->ChooseBytecode();
    QString special = n->SpecialBytecode();
    n->UpdateServicesImportant();
    if(special.length() == 0 && previousBytecode != bytecode)
    {
      LogDebug("Nabaztag " + QString(n->GetID()) + " need to change bytecode : " + bytecode);
      needBytecode = true;
      previousBytecode = bytecode;
    }

    QDateTime now = QDateTime::currentDateTime();
    Instance().lastPing.insert(n, now);
    if(log)
    {
      QsLogging::Logger::DumpLog(request.GetRawURI(), QString("V1 on OJN (%1)").arg(QString(n->GetID())));
    }
    QString last = n->GetGlobalSetting("Last Ping", QString()).toString();
    if(last == "" || n->GetGlobalSetting("Last PingConnection", QString()) == "")
    {
      n->SetGlobalSetting("Last PingConnection", now);
      n->SetVersion(1);
    }
    else
    {
      QDateTime lastPing = QDateTime::fromString(last, "yyyy-MM-ddThh:mm:ss");
      if(lastPing.addSecs(2700) < now)
      {
        n->SetGlobalSetting("Last PingConnection", now);
        //n->SetPluginSetting("clock", "lastDate", now.toString("yyyy-MM-dd hh:mm:ss"));
      }
    }
    //LogDebug(n->GetBunnyName() + " ping the server");
    QList<QByteArray> messages;

    QByteArray answer;// = QByteArray::fromHex("7F");
    n->SetGlobalSetting("Last Ping", now);

    // Save last bunny IP
    n->SetGlobalSetting("LastIP", request.GetIP());

    // sn=00904BBD79F1&ex=000000000000&v=20&st=01&tc=00000001&tn=00000001
    if(request.HasArg("st") && request.GetArg("st") == "00")
    {
      n->SetGlobalSetting("asleep", false);
      n->SetGlobalSetting("Last PingConnection", now);
      n->SetVersion(1);
      LogDebug("Nabaztag" + n->GetBunnyName() + " just arrived on the server. Load bytecode: " + bytecode);
      answer = NabaztagManager::buildPacket( NabaztagManager::loadBytecode(bytecode, n) + NabaztagManager::setDelay(10) );
    }
    else if(special.length() > 0)
    {
      if(previousBytecode != special)
      {
        LogDebug("Nabaztag " + QString(n->GetID()) + " need special bytecode : " + special);
        previousBytecode = special;
        answer = NabaztagManager::buildPacket( NabaztagManager::loadBytecode(special, n) + NabaztagManager::setDelay(10) );
      }
    }
    else if(needBytecode)
    {
      LogDebug("Nabaztag " + QString(n->GetID()) + " need new bytecode : " + bytecode);
      answer = NabaztagManager::buildPacket( NabaztagManager::loadBytecode(bytecode, n) + NabaztagManager::setDelay(10));
    }
    else
    {
      if(answer.length() == 0)
      {
        QStringList files = Instance().soundToSend.value(n);
        files.append(n->AdpFileToLoad());
        if(files.count())
        {
          QString filePlugin = files.takeFirst();
          //filePlugin.replace("broadcast/ojn_local/", GlobalSettings::Get("Config/RealHttpRoot", QString()).toString());
          if(!n->IsSleeping() || n->GetGlobalSetting("Insomniac",false).toBool())
          {
            LogDebug("Nabaztag " + QString(n->GetID()) + " Play ADP file: " + filePlugin);
            //answer = NabaztagManager::buildPacket( NabaztagManager::insertAdpFile(filePlugin, 1) )
            answer = getAMsgForADP(1,filePlugin);
          }
        }
        if(files.count() > 0)
        {
          Instance().soundToSend.insert(n, files);
        }
        else
        {
          Instance().soundToSend.remove(n);
        }
      }
      if(answer.length() == 0)
      {
        bool change = false;
        if(request.HasArg("sd"))
        {
          //LogDebug(n->GetBunnyName() + " event change");
          if(request.GetArg("sd") == "0002")
          {
            LogDebug("Nabaztag " + QString(n->GetID()) +"/" + n->GetBunnyName()+ " Event finished");
            answer = NabaztagManager::buildPacket(NabaztagManager::loadBytecode(bytecode, n));// + NabaztagManager::setDelay(10);
          }
          else if(request.GetArg("sd") == "0001")
          {
            LogDebug("Nabaztag " + QString(n->GetID()) +"/" + n->GetBunnyName()+ " Double click");
            n->OnClick(PluginInterface::DoubleClick);
          }
          else if(request.GetArg("sd") == "0003")
          {
            LogDebug("Nabaztag " + QString(n->GetID()) +"/" + n->GetBunnyName()+ " Single click");
            n->OnClick(PluginInterface::SingleClick);
          }
          else if(request.GetArg("sd") == "0004")
          {
            LogDebug("Nabaztag " + QString(n->GetID()) +"/" + n->GetBunnyName()+ " Long click");
            n->OnClick(PluginInterface::SingleClick);
          }
          else if(request.GetArg("sd") == "01FF")
          {
            LogDebug("Nabaztag " + QString(n->GetID()) +"/" + n->GetBunnyName()+ " Unknown 01FF");
            answer = NabaztagManager::loadBytecode(bytecode, n) + NabaztagManager::setDelay(10);
          }
          else
          {
            LogDebug("Nabaztag " + QString(n->GetID()) +"/" + n->GetBunnyName()+ " Ears move");
          }
          change = true;
        }
        /*if(false && change) // || true
        {
          QByteArray services;

          services += QByteArray::fromHex(request.GetArg("tc").toLatin1());
          // TaiChi
          services += QByteArray::fromHex(QString::number(AmbientPacket::Service_TaiChi).toLatin1() + "FF");

          // Empty * 7
          for(int i = 0; i < 7; i++)
            services += QByteArray::fromHex("0000");

          // left ear
          services += QByteArray::fromHex("00"); // 02
          // right ear
          services += QByteArray::fromHex("00"); // 04
          // nose
          services += QByteArray::fromHex("00");

          services += QByteArray::fromHex("FF");

          answer += QByteArray::fromHex("7F04") + QByteArray::fromHex(NabaztagManager::encodeHexInt(services.length() / 2, 6)) + services;
        }
        */
      }
    }
    if(answer.length() == 0)
    {
      messages.append( NabaztagManager::setDelay(10) );
      messages.append( NabaztagManager::setServiceData(n) );
      answer = NabaztagManager::buildPacket( messages );
    }

    s->write(answer);
    if(log)
    {
      QsLogging::Logger::DumpLog(answer.toHex(), "V1 answer");
    }
  }
}

void NabaztagManager::InitApiCalls()
{
}

void NabaztagManager::Close()
{
}

