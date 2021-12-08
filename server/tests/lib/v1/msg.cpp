#include <iostream>

#ifndef COMMON_MAIN
  #define CATCH_CONFIG_MAIN
#endif
#include <catch.hpp>

#include <QCoreApplication>
#include "nabaztagmanager.h"

TEST_CASE("LoadByteCode","[Lib][v1][Msg]")
{
  int argc=1; char* argv[] = {(char*)"./v1_msg"};
  auto* qapp = new QCoreApplication(argc,argv);
  GlobalSettings::Init("data/conf/");

  auto& nabMgr = NabaztagManager::Instance();
  nabMgr.Init();
  // Load template bytecode to play ADP file later
  REQUIRE(nabMgr.loadAMsgBytecode("data/lib/v1/template.nadp"));
  // Play empty.adp
  {
    const auto& bc = nabMgr.getAMsgForADP(0x01, "data/lib/v1/adp/empty.adp");
    REQUIRE_FALSE(bc.isEmpty());
    const auto& ref_bc = nabMgr.readFile("data/lib/v1/nadp/empty.nadp")+"\n"+nabMgr.getSignature();
    //qDebug() << bc;
    //qDebug() << ref_bc;
    REQUIRE(bc == ref_bc);
  }
  // Play empty2.adp
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
