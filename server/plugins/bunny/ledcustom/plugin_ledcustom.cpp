#include "plugin_ledcustom.h"

#include "account.h"
#include "bunny.h"
#include "bunnymanager.h"
#include "packets/chorconfigpacket.h"
#include "log.h"
#include "packets/serviceconfigpacket.h"
#include "settings.h"
#include "translator.h"
#include "tts/ttsmanager.h"

PluginLedcustom::PluginLedcustom()
  : PluginInterface("ledcustom", "Custom LED language plugin", BunnyV2Plugin | PeriodPlugin /*| PremiumPlugin*/ | ApiPlugin )
{
}

const QHash<QString, QString> PluginLedcustom::GetChangelog(void)
{
  QHash<QString, QString> revisions;
  revisions.insert("1.0.0", "Initial version");
  revisions.insert("1.0.1", "Add supported languages and bootcode informations");
  revisions.insert("1.0.2", "Catch debug message");
  revisions.insert("1.1.0", "Add custom choregraphies support");
  revisions.insert("1.1.1", "Add choregraphies fetch from bunny");
  revisions.insert("1.1.2", "Fix ACL bug");
  return revisions;
}

bool PluginLedcustom::XmppBunnyMessage(Bunny * b, QByteArray const& data)
{
  QRegExp rx("<message[^>]*>(.*)</message>");
  rx.setMinimal(true);
  int pos = 0;
  if (rx.indexIn(data) != -1)
  {
    bool used = false;
    while (rx.indexIn(data, pos) != -1)
    {
      pos = rx.indexIn(data, pos) + rx.matchedLength();
      QString message = rx.cap(1);

      rx.setPattern("<debug xmlns=\"OJN:nabaztag:debug:service\"");
      if (rx.indexIn(message) != -1)
      {
        used = true;
      }
      pos++;
    }
    return used;
  }

  rx.setPattern("<iq[^>]*><command[^>]*node='getchoregraphies' status='completed'[^>]*>(.*)</iq>");
  if (rx.indexIn(data) != -1)
  {
    BunnyData data;
    data.updated = QDateTime::currentDateTime();
    data.bunnyId = b->GetID();
    QList<Chor> list;

    QString message = rx.cap(1);
    rx.setPattern("<field var='choregraphy\\.(\\d+)\\.(\\d+)'><tempo>(\\d*)</tempo><leds>([0-9,]*)</leds></field>");
    pos = 0;
    while ((pos = rx.indexIn(message, pos)) != -1)
    {
      Chor c;
      c.service = rx.cap(1).toInt();
      c.value = rx.cap(2).toInt();
      c.tempo = rx.cap(3).toInt();
      c.leds = rx.cap(4);
      //LogDebug("Choregraphy (" + QString::number(c.service) + "," + QString::number(c.value) + ") : " + c.leds + " / " + QString::number(c.tempo));
      list.append(c);
      pos += rx.matchedLength();
    }
    data.chors = list;
    services.insert(b->GetID(), data);
    return true;
  }
  return false;
}

void PluginLedcustom::updateBunny(Bunny * b)
{
  QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Services", QMap<QString, QVariant>()).toMap();
  QMapIterator<QString, QVariant> i(list);
  QByteArray message;
  while (i.hasNext()) {
    i.next();
    QString service = i.key();
    QStringList values = i.value().toString().split("|SEPARATOR|");
    QString interval = values.at(0);
    QString url = values.at(1);
    message += service.toLatin1() + " " + interval.toLatin1() + ";" + url.toLatin1() + "\n";
/*
    QString data = QString("<iq type='set' to='%1@%2/%3' from='%2@%2/server' id='exec1'><command xmlns='http://jabber.org/protocol/commands' action='set' node='%5' value='%4' option='%6'/></iq>").arg(QString(b->GetID()), GlobalSettings::GetString("OpenJabNabServers/XmppServer"), QString(b->GetXmppResource()), url, service, interval);
    b->SendExpertData(data.toLatin1());
*/
  }
  if(message.length())
  {
//		LogDebug(QString(message.toHex()));
    b->SendPacket(ServiceconfigPacket(message), GetName());
  }
}

void PluginLedcustom::updateChoregraphies(Bunny * b)
{
  QByteArray message;
  QMap<QString, QVariant> list = b->GetPluginSetting(GetName(), "Chors", QMap<QString, QVariant>()).toMap();
  QMapIterator<QString, QVariant> i(list);
  while (i.hasNext()) {
    i.next();
    QString service = i.key();
    QString choregraphy = i.value().toString();
//	QByteArray message = "12 10;1,1,1|12;2,2,2|14;3,4,5,1,2,3,2,3,4\n";
//	message += "15 32;2,2,2|32;2,2,3|32;2,3,3|32;2,3,1|32;2,3,1,2,3,1,2,3,1,2,3,0|32;2,3,1,2,3,0\n";
    message += service.toLatin1() + " " + choregraphy.toLatin1() + "\n";
  }
  if(message.length())
  {
//		LogDebug(QString(message.toHex()));
    b->SendPacket(ChorconfigPacket(message), GetName());
  }
}

void PluginLedcustom::SendChoregraphiesRequest(Bunny * b)
{
  QString data = QString("<iq type='set' to='%1@%2/%3' from='%2@%2/server' id='exec1'><command xmlns='http://jabber.org/protocol/commands' node='%4' action='execute'/></iq>").arg(QString(b->GetID()), GlobalSettings::GetString("OpenJabNabServers/XmppServer"), QString(b->GetXmppResource()), "getchoregraphies");
  b->SendExpertData(data.toLatin1());
}

void PluginLedcustom::OnBunnyConnect(Bunny * b)
{
  updateChoregraphies(b);
  updateBunny(b);
}

void PluginLedcustom::InitApiCalls()
{
  DECLARE_PLUGIN_BUNNY_API_CALL("service()", &PluginLedcustom::Api_Service);
  DECLARE_PLUGIN_BUNNY_API_CALL("chor()", &PluginLedcustom::Api_Chor);
}

PLUGIN_BUNNY_API_CALL(PluginLedcustom::Api_Chor)
{
  if(!hRequest.HasArg("action"))
    return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

  QString action = hRequest.GetArg("action");

  if(action == "list")
  {
    return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Chors", QMap<QString, QVariant>()).toMap());
  }
  else if(action == "request")
  {
    SendChoregraphiesRequest(bunny);
    return new ApiAnswers::Ok(Translator::tr("Choregraphies requested for bunny '%1'", account).arg(QString(bunny->GetID())));
  }
  else if(action == "fetch")
  {
    QString chors = "<chors>";
    BunnyData data = services.value(bunny->GetID());
    foreach (Chor chor, data.chors)
    {
      //LogDebug("Choregraphy (" + QString::number(chor.service) + "," + QString::number(chor.value) + ") : " + chor.leds + " / " + QString::number(chor.tempo));
      chors += "<chor service='" + QString::number(chor.service) + "' value='" + QString::number(chor.value) + "' tempo='" + QString::number(chor.tempo) + "'>";
      chors += chor.leds;
      chors += "</chor>";
    }
    chors += "</chors>";
    return new ApiAnswers::Xml(chors);

  }
  else if(action == "add")
  {
    if(!hRequest.HasArg("value"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("value", GetName()));

    int value = hRequest.GetArg("value").toInt();

    if(!hRequest.HasArg("service"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("service", GetName()));

    QString service = hRequest.GetArg("service");

    if(!hRequest.HasArg("tempo"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("tempo", GetName()));

    QString tempo = hRequest.GetArg("tempo");

    if(!hRequest.HasArg("leds"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("leds", GetName()));

    QString leds = hRequest.GetArg("leds");

    QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Chors", QMap<QString, QVariant>()).toMap();
    QStringList chors;
    QString chor = list.value(service).toString();
    if(chor.length())
    {
      chors = chor.split("|");
    }
    if(value > chors.length())
    {
      value = chors.length();
    }
    chors.insert(value, tempo + ";" + leds);
    chor = chors.join("|");

    list.insert(service, chor);
    bunny->SetPluginSetting(GetName(), "Chors", list);

    updateChoregraphies(bunny);
    return new ApiAnswers::Ok(Translator::tr("Choregraphy '%1' defined for bunny '%2'", account).arg(service, QString(bunny->GetID())));
  }
  else if(action == "del")
  {
    if(!hRequest.HasArg("value"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("value", GetName()));

    int value = hRequest.GetArg("value").toInt();

    if(!hRequest.HasArg("service"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("service", GetName()));

    QString service = hRequest.GetArg("service");

    QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Chors", QMap<QString, QVariant>()).toMap();
    if(list.contains(service))
    {
      QStringList chors;
      QString chor = list.value(service).toString();
      if(chor.length())
      {
        chors = chor.split("|");
      }
      if(value > chors.length())
      {
        return new ApiAnswers::Error(Translator::tr("Choregraphy '%1' is not defined for bunny '%2'", account).arg(service + "." + QString::number(value), QString(bunny->GetID())));
      }
      chors.removeAt(value);
      chor = chors.join("|");

      if(chor.length())
      {
        list.insert(service, chor);
      }
      else
      {
        list.remove(service);
      }
      bunny->SetPluginSetting(GetName(), "Chors", list);

      updateChoregraphies(bunny);
      return new ApiAnswers::Ok(Translator::tr("Choregraphy '%1' removed for bunny '%2'", account).arg(service + "." + QString::number(value), QString(bunny->GetID())));
    }
    return new ApiAnswers::Error(Translator::tr("Choregraphy '%1' is not defined for bunny '%2'", account).arg(service + "." + QString::number(value), QString(bunny->GetID())));
  }
  else if(action == "remove")
  {
    if(!hRequest.HasArg("service"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("service", GetName()));

    QString service = hRequest.GetArg("service");

    QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Chors", QMap<QString, QVariant>()).toMap();
    if(list.contains(service))
    {
      list.remove(service);
      bunny->SetPluginSetting(GetName(), "Chors", list);

      updateChoregraphies(bunny);
      return new ApiAnswers::Ok(Translator::tr("Choregraphy '%1' removed for bunny '%2'", account).arg(service, QString(bunny->GetID())));
    }
    return new ApiAnswers::Error(Translator::tr("Choregraphy '%1' is not defined for bunny '%2'", account).arg(service, QString(bunny->GetID())));
  }
  else
  {
    return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
  }
}

PLUGIN_BUNNY_API_CALL(PluginLedcustom::Api_Service)
{
  if(!hRequest.HasArg("action"))
    return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("action", GetName()));

  QString action = hRequest.GetArg("action");

  if(action == "list")
  {
    return new ApiAnswers::MappedList(bunny->GetPluginSetting(GetName(), "Services", QMap<QString, QVariant>()).toMap());
  }
  else if(action == "add")
  {
    if(!hRequest.HasArg("service"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("service", GetName()));

    QString service = hRequest.GetArg("service");

    if(!hRequest.HasArg("url"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("url", GetName()));

    QString url = hRequest.GetArg("url");

    QString interval = "60";
    if(hRequest.HasArg("interval"))
      interval = hRequest.GetArg("interval");

    QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Services", QMap<QString, QVariant>()).toMap();
    list.insert(service, interval + "|SEPARATOR|" + url);
    bunny->SetPluginSetting(GetName(), "Services", list);

    updateBunny(bunny);
    return new ApiAnswers::Ok(Translator::tr("Service '%1' defined for bunny '%2'", account).arg(service, QString(bunny->GetID())));
  }
  else if(action == "remove")
  {
    if(!hRequest.HasArg("service"))
      return new ApiAnswers::Error(Translator::tr("Missing argument '%1' for plugin %2", account).arg("service", GetName()));

    QString service = hRequest.GetArg("service");

    QMap<QString, QVariant> list = bunny->GetPluginSetting(GetName(), "Services", QMap<QString, QVariant>()).toMap();
    if(list.contains(service))
    {
      list.remove(service);
      bunny->SetPluginSetting(GetName(), "Services", list);

      updateBunny(bunny);
      return new ApiAnswers::Ok(Translator::tr("Service '%1' removed for bunny '%2'", account).arg(service, QString(bunny->GetID())));
    }
    return new ApiAnswers::Error(Translator::tr("Service '%1' is not defined for bunny '%2'", account).arg(service, QString(bunny->GetID())));
  }
  else
  {
    return new ApiAnswers::Error(Translator::tr("Bad argument '%1' for plugin %2", account).arg("action", GetName()));
  }
}
