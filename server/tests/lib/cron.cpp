#ifndef COMMON_MAIN
  #define CATCH_CONFIG_MAIN
#endif
#include <catch.hpp>
#include <chrono>
#include <thread>

#include <QTimer>

#include <QCoreApplication>
#include "cron.h"
#include "plugininterface.h"
#include "settings.h"

class dummyPlugin
  : public PluginInterface
{
  Q_INTERFACES(PluginInterface)
  Q_PLUGIN_METADATA(IID "ojn.plugin.bunny.earinfo" )

public:
  dummyPlugin()
    : PluginInterface("dummyPlugin", "dummyPlugin", CronPlugin)
  {
    LogInfo("[dummyPlugin::ctor]");
    Cron::RegisterOneShot(this, 1, nullptr, Cron::Classic, QVariant::fromValue(12.34),
                          [&](Bunny*, QVariant data, Cron::CronType t)
      {
        v1 = data.toDouble();
        LogInfo(QString("  Cron Type %1 Data %2").arg((int)t).arg(v1));
      });
    Cron::RegisterOneShot(this, 1, nullptr, Cron::Classic, QVariant::fromValue(56.78));
    v1=v2=0;
  }

  virtual void OnCron(Bunny*, QVariant data, unsigned int t) override
  {
    v2 = data.toDouble();
    LogInfo(QString("onCron Type %1 Data %2").arg((int)t).arg(v2));
  }

  ~dummyPlugin()
  {
    LogInfo("[dummyPlugin::dtor]");
    Cron::UnregisterAll(this);
  }

  double v1,v2;
};

TEST_CASE("OneShot 1min","[Lib][Cron]")
{
  int argc=1; char* argv[] = {(char*)"./cron"};
  auto* qapp = new QCoreApplication(argc,argv);
  GlobalSettings::Init("data/conf/");
  auto& cronMgr = Cron::Instance();
  cronMgr.Init();
  {
    dummyPlugin dPl;
    QTimer::singleShot(90000, [&](void) { qapp->exit(); });
    qapp->exec();
    REQUIRE(dPl.v1 == 12.34);
    REQUIRE(dPl.v2 == 56.78);
  }
  delete qapp;
}
