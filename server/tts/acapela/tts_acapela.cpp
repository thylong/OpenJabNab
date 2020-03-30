#include <QDateTime>
#include <QCryptographicHash>
#include <QMapIterator>
#include <QNetworkAccessManager>
#include <QUrl>
#include <QNetworkRequest>
#include <QNetworkReply>
#include "tts_acapela.h"
#include "log.h"

#include <QJsonDocument>

#include <iostream>
TTSacapela::TTSacapela():TTSInterface("acapela", "acapela")
{
  Voice v;
  v.limit = 5000;

/**
Arabic                  Leila         Mehdi           Nizar               Salma   
Catalan                 Laia   
Czech                   Eliska   
Danish                  Mette         Rasmus   
Dutch (Belgium)         Zoe           Jeroen          JeroenHappy         
                        JeroenSad     Sofie   
Dutch (Netherlands)     Jasmijn       Daan            Femke               Max   
English (AU)            Tyler         Lisa   
English (India)         Deepa   
English (Scottish)      Rhona   
English (UK)            Rachel        Graham          Lucy                Nizareng        Peter             
                                      PeterHappy      PeterSad            QueenElizabeth    
English (USA)           Sharon        Karen           Kenny               Laura           Micah
                                      Nelly           Rod                 Ryan            Saul   
                                      Tracy           Will                WillBadGuy      WillFromAfar    
                                      WillHappy       WillLittleCreature  WillOldMan      WillSad    
                                      WillUpClose    
Faroese                 Hanna         Hanus   
Finnish                 Sanna   
French (Belgium)        Manon-be      Alice-be        Anais-be            Antoine-be      Bruno-be          
                        Claire-be     Julie-be        Margaux-be   
French (Canada)         Louise   
French (France)         Manon         Alice           Anais               Antoine         AntoineFromAfar   
                        AntoineHappy  AntoineSad      AntoineUpClose      Bruno           Claire   
                        Julie         Margaux         MargauxHappy        MargauxSad    
German                  Claudia       Andreas         ClaudiaSmile        Julia           
                        Klaus         Sarah   
Greek                   Dimitris      DimitrisHappy   DimitrisSad    
Italian                 Fabiana       Chiara          Vittorio   
Japanese                Sakura   
Korean                  Minji   
Mandarin                Lulu   
Norwegian               Bente         Kari            Olav   
Polish                  Ania   
Portuguese (Brazil)     Marcia   
Portuguese (Portugal)   Celia   
Russian                 Alyona   
Sami (North)            Biera         Elle   
Spanish (Spain)         Ines          Maria   
Spanish (US)            Rodrigo       Rosa   
Swedish                 Elin          Emil            Emma                Erik   
Swedish (Finland)       Samuel   
Swedish (Gothenburg)    Kal   
Swedish (Scanian)       Mia   
Turkish                 Ipek
*/

    // FR
  v.language = "fr";

  v.genre = Voice::Woman;
  v.name = "anais22k";      v.label = "Anais";      voiceList.insert(v.name, v);
  v.name = "alice22k";      v.label = "Alice";      voiceList.insert(v.name, v);
  v.name = "claire22k";     v.label = "Claire";     voiceList.insert(v.name, v);
  v.name = "julie22k";      v.label = "Julie";      voiceList.insert(v.name, v);
  v.name = "margaux22k";    v.label = "Margaux";    voiceList.insert(v.name, v);

  v.genre = Voice::Man;
  v.name = "antoine22k";    v.label = "Antoine";    voiceList.insert(v.name, v);
  v.name = "bruno22k";      v.label = "Bruno";      voiceList.insert(v.name, v);

    // EN
  v.language = "en";

  v.genre = Voice::Woman;
  v.name = "anais22k";      v.label = "Rachel";     voiceList.insert(v.name, v);
  v.name = "queenelizabeth22k"; v.label = "QueenElizabeth"; voiceList.insert(v.name, v);
  v.name = "sharon22k";     v.label = "Sharon";     voiceList.insert(v.name, v);
  v.name = "karen22k";      v.label = "Karen";      voiceList.insert(v.name, v);
  v.name = "laura22k";      v.label = "Laura";      voiceList.insert(v.name, v);
  v.name = "nelly22k";      v.label = "Nelly";      voiceList.insert(v.name, v);
  v.name = "tracy22k";      v.label = "Tracy";      voiceList.insert(v.name, v);

  v.genre = Voice::Man;
  v.name = "graham22k";     v.label = "Graham";     voiceList.insert(v.name, v);
  v.name = "peter22k";      v.label = "Peter";      voiceList.insert(v.name, v);
  v.name = "rod22k";        v.label = "Rod";        voiceList.insert(v.name, v);
  v.name = "ryan22k";       v.label = "Ryan";       voiceList.insert(v.name, v);
  v.name = "saul22k";       v.label = "Saul";       voiceList.insert(v.name, v);
  v.name = "will22k";       v.label = "Will";       voiceList.insert(v.name, v);
}

TTSacapela::~TTSacapela()
{
}

QString TTSacapela::CreateNewSound(QString text, QString voice, bool forceOverwrite)
{
  QEventLoop loop;

  if(!voiceList.contains(voice))
  {
    LogDebug(QString("TTS Acapela: Unknown voice %1, fallback to fr_female").arg(voice));
    voice = "fr_female";
  }

  // Check (and create if needed) output folder
  QDir outputFolder = ttsFolder;
  if(!outputFolder.exists(voice))
    outputFolder.mkdir(voice);

  if(!outputFolder.cd(voice))
  {
    LogError(QString("TTS Acapela: Cant create TTS Folder : %1").arg(ttsFolder.absoluteFilePath(voice)));
    return QString();
  }

  // Compute fileName
  QString fileName = QCryptographicHash::hash(text.toLatin1(), QCryptographicHash::Md5).toHex().append(".mp3");
  QString filePath = outputFolder.absoluteFilePath(fileName);

  if(!forceOverwrite && QFile::exists(filePath))
  {
    //LogWarning(QString("TTS Acapela: Reuse file"));
    return ttsHTTPUrl.arg(voice, fileName).toLatin1();
  }

  // Get Authentication IDs ids
  QNetworkAccessManager http;
  QNetworkRequest req(QUrl("https://www.acapela-group.com/www/static/website/demoOptionsDef.php"));

  QNetworkReply* rep = http.get(req);
  QObject::connect(rep, SIGNAL(finished()), &loop, SLOT(quit()));
  QObject::connect(rep, SIGNAL(error(QNetworkReply::NetworkError)), &loop, SLOT(quit()));
  loop.exec();

  const auto& answer = rep->readAll();
  if(answer.size() == 0)
  {
    LogError("TTS Acapela: Empty IDs =(");
    delete rep;
    return QString();
  }

  ///var vaasOptions = {"login":"AcapelaGroup_WebDemo_BUTransport","app":"AcapelaGroup_WebDemo_BUTransport","json_service_url":"https:\/\/H-IR-SSD-1.acapela-group.com\/webservices\/1-60-00\/UrlMaker.json","session":{"start":1585598183,"time":10800,"key":"2096218979-fa191161f53b76340fd0cb9a4a79217b5b0b96d6b9c6464bc361186f11e29e28"},"voice":null};
  
  QString json_src = QString(answer).replace("var vaasOptions = ","");
  json_src = json_src.replace("};","}");
  json_src = json_src.replace("\\/","/");
  
  //LogDebug(QString("TTS Acapela: Rep1: %1").arg(json_src));
  const auto& json_doc = QJsonDocument::fromJson(json_src.toUtf8());
  const auto& json = json_doc.object();
  //LogDebug(QString("TTS Acapela: JSON1: %1").arg(QString(json_doc.toJson())));
  delete rep;

  if(!(json.contains("login") && json.contains("app") && 
       json.contains("json_service_url") && json.contains("session")
      )
    )
  {
    LogDebug(QString("TTS Acapela: Auth JSON array is incomplete"));
    return QString();
  }
  const auto& jsonS = json["session"].toObject();
  if(!(jsonS.contains("start") && jsonS.contains("time") && jsonS.contains("key")))
  {
    LogDebug(QString("TTS Acapela: Auth JSON session array is incomplete"));
    return QString();
  }
  const auto& login  = json["login"].toString();
  const auto& app    = json["app"].toString();
  const auto& url    = json["json_service_url"].toString();
  const auto& start  = jsonS["start"].toInt();
  const auto& time   = jsonS["time"].toInt();
  const auto& key    = jsonS["key"].toString();

  // Make MP3!
  // https://h-ir-ssd-1.acapela-group.com/webservices/1-60-00/UrlMaker.json
  QNetworkRequest req2(QUrl(url.toStdString().c_str()));
  req2.setRawHeader("Content-type","application/x-www-form-urlencoded");
  QByteArray ContentData;
  //cl_login=AcapelaGroup&cl_app=AcapelaGroup_WebDemo_HTML&session_start=1585596267&session_time=10800&session_key=2096218979-2698bdb6b51d6aeac79e2d89a856ab72ba1f01e01b1f662e92d4bef2c74dd304&req_voice=anais22k&req_text=Bonjour%2C+je+suis+Ana%C3%AFs.%0D%0A
  ContentData +=  "cl_login="+login+"&cl_app="+app;
  ContentData += "&session_start="+QString::number(start)+"&session_time="+QString::number(time)+"&session_key="+key;
  ContentData += "&req_voice="+voice+"&req_text="+QUrl::toPercentEncoding(text);
  //LogDebug(QString("ContentData: %1").arg(QString(ContentData)));

  QNetworkReply* rep2 = http.post(req2,ContentData);
  QObject::connect(rep2, SIGNAL(finished()), &loop, SLOT(quit()));
  QObject::connect(rep2, SIGNAL(error(QNetworkReply::NetworkError)), &loop, SLOT(quit()));
  loop.exec();

  const auto& answer2 = rep2->readAll();
  if(answer2.size() == 0)
  {
    LogError("TTS Acapela: Empty MP3 URL");
    delete rep2;
    return QString();
  }

  //{"w":"","snd_time":"5264","get_count":"0","snd_id":"_f1f1752be8b9a","asw_pos_init_offset":"0","asw_pos_text_offset":"0","snd_url":"https:\/\/h-ir-ssd-1.acapela-group.com\/MESSAGES\/012099097112101108097071114111117112\/AcapelaGroup_WebDemo_HTML\/sounds\/_f1f1752be8b9a.mp3","snd_size":"32098","res":"OK","create_echo":""}
  //LogDebug(QString("TTS Acapela: Rep2: %1").arg(QString(answer2)));
  const auto& json_doc2 = QJsonDocument::fromJson(QString(answer2).toUtf8());
  const auto& json2 = json_doc2.object();
  //LogDebug(QString("TTS Acapela: JSON1: %1").arg(QString(json_doc2.toJson())));
  delete rep2;

  if(!json2.contains("snd_url"))
  {
    LogDebug(QString("TTS Acapela: MP3 JSON array is incomplete"));
    return QString();
  }
  QString snd_url  = json2["snd_url"].toString();

  snd_url = snd_url.replace("\\/","/");

  // Get the MP3 !
  QNetworkRequest req3(QUrl(snd_url.toStdString().c_str()));

  QNetworkReply* rep3 = http.get(req3);
  QObject::connect(rep3, SIGNAL(finished()), &loop, SLOT(quit()));
  QObject::connect(rep3, SIGNAL(error(QNetworkReply::NetworkError)), &loop, SLOT(quit()));
  loop.exec();

  const auto& answer3 = rep3->readAll();
  if(answer3.size() == 0)
  {
    LogError("TTS Acapela: Empty MP3");
    delete rep3;
    return QString();
  }

  QFile file(filePath);
  if (!file.open(QIODevice::WriteOnly))
  {
    LogError("TTS Acapela: Cannot open sound file for writing");
    delete rep3;
    return QString();
  }
  file.write(answer3);
  file.close();
  delete rep3;

  return ttsHTTPUrl.arg(voice, fileName).toLatin1();
}


