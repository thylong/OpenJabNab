#include <iostream>

#ifndef COMMON_MAIN
  #define CATCH_CONFIG_MAIN
#endif
#include <catch.hpp>

#include <QCoreApplication>
#include "nabaztagmanager.h"

TEST_CASE("Hi","[Lib][v1][Msg]")
{
  int argc=1; char* argv[] = {"./v1_msg"};
  auto* qapp = new QCoreApplication(argc,argv);
  auto& nabMgr = NabaztagManager::Instance();
  nabMgr.Init();
  REQUIRE(nabMgr.loadAMsgBytecode("data/lib/v1/template.nadp"));
  {
    const auto& bc = nabMgr.getAMsgForADP(0x01, "data/lib/v1/adp/empty.adp");
    REQUIRE_FALSE(bc.isEmpty());
    const auto& ref_bc = nabMgr.readFile("data/lib/v1/nadp/empty.nadp")+"\n"+nabMgr.getSignature();
    //qDebug() << bc;
    //qDebug() << ref_bc;
    REQUIRE(bc == ref_bc);
  }
  {
    const auto& bc = nabMgr.getAMsgForADP(0x01, "data/lib/v1/adp/empty2.adp");
    REQUIRE_FALSE(bc.isEmpty());
    const auto& ref_bc = nabMgr.readFile("data/lib/v1/nadp/empty2.nadp")+"\n"+nabMgr.getSignature();
    //qDebug() << bc;
    //qDebug() << ref_bc;
    REQUIRE(bc == ref_bc);
  }
  nabMgr.Close();
  delete qapp; 
}
