cmake_minimum_required(VERSION 3.7)
project(ojn)

include(cmake/version.cmake)

add_library(${PROJECT_NAME} SHARED
#WIN32
  browsercache.h
  browserclient.h
  cron.h
  httphandler.h
  xmpphandler.h

  account.cpp
  accountmanager.cpp
  ambientpacket.cpp
  apimanager.cpp
  browsercache.cpp
  browserclient.cpp
  bunny.cpp
  bunnymanager.cpp
  chorconfigpacket.cpp
  choregraphy.cpp
  configpacket.cpp
  cron.cpp
  dbmanager.cpp
  httphandler.cpp
  httprequest.cpp
  iq.cpp
  log.cpp
  messagepacket.cpp
  nabaztagmanager.cpp
  packet.cpp
  pluginmanager.cpp
  QsLog/QsLog.cpp
  QsLog/QsLogDest.cpp
  QsLog/QsLogDestFile.cpp
  QsLog/QsLogDestConsole.cpp
  serviceconfigpacket.cpp
  settings.cpp
  sentencemanager.cpp
  sleeppacket.cpp
  ttsmanager.cpp
  translator.cpp
  xmpphandler.cpp
  ztamp.cpp
  ztampmanager.cpp

  ${CMAKE_CURRENT_BINARY_DIR}/version.c
)

target_link_libraries(${PROJECT_NAME}
PUBLIC
  Qt5::Core
  Qt5::Network
  Qt5::Sql
)

if(WIN32)
  target_compile_definitions(${PROJECT_NAME}
  PUBLIC
    OJN_MAKEDLL
  )
  target_link_libraries(${PROJECT_NAME}
    Ws2_32
  )
endif()

target_include_directories(${PROJECT_NAME}
PUBLIC
  ${CMAKE_CURRENT_SOURCE_DIR}
)
