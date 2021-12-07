#ifndef _PLUGINCOLORBREATHING_H_
#define _PLUGINCOLORBREATHING_H_

#include "plugininterface.h"

class PluginColorbreathing
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.colorbreathing" )

public:
  PluginColorbreathing();

  virtual void OnInitPacket(const Bunny *, AmbientPacket &, SleepPacket &) override;

  virtual const QString GetVersion(void) override { return "2.1.0"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "all"; }
  virtual const QHash<QString, QString> GetChangelog(void) override
  {
    QHash<QString, QString> revisions;
    revisions.insert("2.0.1", "New API to set and save color");
    revisions.insert("2.1.0", "Add support for Nabaztag V1");
    return revisions;
  }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("color", "string");
    list.insert("saveandset", "string");
    return list;
  }

private:
  virtual ~PluginColorbreathing() = default;

  void SetServices(Bunny *);

  QString OnApiColor(Bunny *, QVariant);
  QString OnApiSaveandset(Bunny *, QVariant);

  virtual void InitApiCalls() override;
  PLUGIN_BUNNY_API_CALL(Api_GetColorList);
  PLUGIN_BUNNY_API_CALL(Api_SetColor);
  PLUGIN_BUNNY_API_CALL(Api_GetColor);
  PLUGIN_BUNNY_API_CALL(Api_Color);

  QHash<QString, unsigned char> availableColorsV2;
  QHash<QString, unsigned char> availableColorsV1;
};

#endif
