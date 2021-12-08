#include <iostream>
#include "settings.h"

#include "acapela/tts_acapela.h"

#ifndef COMMON_MAIN
  #define CATCH_CONFIG_MAIN
#endif
#include <catch.hpp>

#define OVERWRITE_FILES false

TEST_CASE("Acapella - TestAllVoices","[TTS][Acapela]")
{
  int argc=1; char* argv[] = {(char*)"./test_tts_acapela"};
  auto* qapp = new QCoreApplication(argc,argv);
  GlobalSettings::Init("data/conf/");

  TTSacapela tts;

  std::cout << "Testing all available voices" << std::endl;

  const auto& voices = tts.GetAllVoices();
  for (auto l = voices.cbegin(); l != voices.cend(); ++l)
  {
    std::cout << "- " << l.key().toStdString() << ":" << std::endl;
    const auto& lv = l.value();
    for (auto v = lv.cbegin(); v != lv.cend(); ++v)
    {
      std::cout << "  - " << v.value().toStdString() << std::endl;
      {
                                    // text,   voice,   overwrite
        auto file = tts.CreateNewSound("Hello",v.key(), OVERWRITE_FILES);
        //std::cout << "Got file: " << file.toStdString() << std::endl;
        REQUIRE(file.length() != 0);
      }
    }
  }
  delete qapp;
}

TEST_CASE("Acapella - TestVoices","[TTS][Acapela]")
{
  int argc=1; char* argv[] = {(char*)"./test_tts_acapela"};
  auto* qapp = new QCoreApplication(argc,argv);
  GlobalSettings::Init("data/conf/");

  TTSacapela tts;

  SECTION("FR")
  {
    std::cout << "FR" << std::endl;
    const auto& lv = tts.GetVoiceListWithName("fr");
    for (auto v = lv.cbegin(); v != lv.cend(); ++v)
    {
      std::cout << "  - " << v.value().toStdString() << std::endl;
      {
                                    // text,   voice,   overwrite
        auto file = tts.CreateNewSound("Bonjour, il est 17h09",v.key(), OVERWRITE_FILES);
        //std::cout << "Got file: " << file.toStdString() << std::endl;
        REQUIRE(file.length() != 0);
      }
    }
  }
  SECTION("EN")
  {
    std::cout << "EN" << std::endl;
    const auto& lv = tts.GetVoiceListWithName("en");
    for (auto v = lv.cbegin(); v != lv.cend(); ++v)
    {
      std::cout << "  - " << v.value().toStdString() << std::endl;
      {
                                    // text,   voice,   overwrite
        auto file = tts.CreateNewSound("Hello, it is 5 PM",v.key(), OVERWRITE_FILES);
        //std::cout << "Got file: " << file.toStdString() << std::endl;
        REQUIRE(file.length() != 0);
      }
    }
  }
  SECTION("DE")
  {
    std::cout << "DE" << std::endl;
    const auto& lv = tts.GetVoiceListWithName("de");
    for (auto v = lv.cbegin(); v != lv.cend(); ++v)
    {
      std::cout << "  - " << v.value().toStdString() << std::endl;
      {
                                    // text,   voice,   overwrite
        auto file = tts.CreateNewSound("Hallo, es ist 17 Uhr",v.key(), OVERWRITE_FILES);
        //std::cout << "Got file: " << file.toStdString() << std::endl;
        REQUIRE(file.length() != 0);
      }
    }
  }
  delete qapp;
}
