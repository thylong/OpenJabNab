#include "plugin_earinfo.h"

#include "bunny.h"
#include "packets/messagepacket.h"
#include "translator.h"
#include "tts/ttsmanager.h"

PluginEarinfo::PluginEarinfo()
  : PluginInterface("earinfo", "Informations about ear positions", BunnyV2Plugin | EarsPlugin)
{
}

bool PluginEarinfo::OnEarsMove(Bunny * b, int left, int right)
{
  QString string = Translator::tr("My ears positions are %1 on left and %2 on right").arg(QString::number(left), QString::number(right));
  TTSAnswer file = TTSManager::CreateSoundWithGenre(string, b->GetVoice(), "fr", Voice::Woman);
  TTSLog(b->GetID(), GetName(), file);
  QByteArray message = "MU " + file.file.toLatin1() + "\nMW\n";
  b->SendPacket(MessagePacket(message), GetName());
  return true;
}
