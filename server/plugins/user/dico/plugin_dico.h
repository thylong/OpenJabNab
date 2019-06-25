#ifndef _PLUGINDICO_H_
#define _PLUGINDICO_H_

#include <QMultiMap>
#include <QTextStream>
#include <QThread>
#include "plugininterface.h"

class PluginDico : public PluginInterface
{
	Q_OBJECT
	Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.user.dico" )
  
private slots:
	QString OnApiSpell(Bunny *, QVariant);

public:
	PluginDico();
	virtual ~PluginDico();
	//virtual bool OnClick(Bunny *, PluginInterface::ClickType);
	//void OnCron(Bunny*, QVariant);
	bool OnVoiceCommand(Bunny*, QString const&, QStringList const&);
	//void OnBunnyConnect(Bunny *);
	//void OnBunnyDisconnect(Bunny *);
	QStringList GetLanguages() { return QStringList() << "fr" << "us" << "uk" << "es" << "de" << "ca"; }
	QString GetVersion() { return "1.0.4"; }
        QHash<QString, QString> GetExtendedApiFunctions()
        {
                QHash<QString, QString> list;
                list.insert("spell", "string");
                return list;
        }


	void InitApiCalls();

private:
	bool spellWord(Bunny *, QString);
	QStringList cleanWords(QString, Bunny *);
};

#endif
