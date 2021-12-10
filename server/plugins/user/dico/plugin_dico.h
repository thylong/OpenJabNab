#ifndef _PLUGINDICO_H_
#define _PLUGINDICO_H_

#include "plugininterface.h"

class PluginDico
  : public PluginInterface
{
  Q_OBJECT
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.dico" )

public:
  PluginDico();

  virtual bool OnVoiceCommand(Bunny*, QString const&, QStringList const&) override;

  virtual const QString GetVersion(void) override { return "1.0.4"; }
  virtual const QStringList GetLanguages(void) override { return QStringList() << "fr" << "us" << "uk" << "es" << "de" << "ca"; }
  virtual const QHash<QString, QString> GetExtendedApiFunctions(void) override
  {
    QHash<QString, QString> list;
    list.insert("spell", "string");
    return list;
  }

public slots:
  QString OnApiSpell(Bunny *, QVariant);

private:
  virtual ~PluginDico() = default;

  bool spellWord(Bunny *, QString);
  QStringList cleanWords(QString, Bunny *);
};

#endif
