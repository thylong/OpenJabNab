#include <time.h>
#include <stdlib.h>

#include "plugin_dice.h"

#include "bunny.h"
#include "httprequest.h"
#include "log.h"
#include "packets/messagepacket.h"
#include "tts/ttsmanager.h"

PluginDice::PluginDice()
  : PluginInterface("dice", "Dice roll",
                    /*BunnyV1Plugin |*/ BunnyV2Plugin | SingleClickPlugin | DoubleClickPlugin
                   )
{
  // Initialize the randomizer
  srand(time(NULL));
  soundToSend.clear();
}

QStringList PluginDice::AdpFileToLoad(Bunny * b)
{
  QStringList list = soundToSend.value(b, QStringList());
  soundToSend.remove(b);
  return list;
}

bool PluginDice::OnClick(Bunny * b, PluginInterface::ClickType)
{
  // Language
  QByteArray Language = b->GetPluginSetting("dice", "PluginConfiguration/Language", "fr").toByteArray();
  // Get a random value and create ID
  quint8 value = rand() % 6 + 1;
  LogDebug(QString(" -- Language : %1 -- Roll dice : %2").arg(Language, QString::number(value)));
  // Send packet to bunny with mp3 to be played
  if(b->GetVersion() == 1)
  {
    QStringList list = soundToSend.value(b, QStringList());
    QString file = GetBroadcastHTTPPath(Language + "/get.mp3");
        file.replace(GlobalSettings::Get("Config/RealHttpRoot", QString()).toString(), "");
    list.append(TTSManager::convertToAdp(file));
    file = GetBroadcastHTTPPath(Language + "/" + QString::number(value) + ".mp3");
        file.replace(GlobalSettings::Get("Config/RealHttpRoot", QString()).toString(), "");
    list.append(TTSManager::convertToAdp(file));
    soundToSend.insert(b, list);
  }
  else
  {
    b->SendPacket(MessagePacket("MU "+GetBroadcastHTTPPath(Language + "/get.mp3")+"\nMW\nMU "+GetBroadcastHTTPPath(Language + "/" + QString::number(value) + ".mp3")+"\nMW\n"), GetName());
  }
  return true;
}
