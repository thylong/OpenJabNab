cmake_minimum_required(VERSION 3.7)
project(tts_bluemix)

ojnTTS(NAME ${PROJECT_NAME}
  FILES tts_bluemix.h tts_bluemix.cpp
  INCLUDE_DIRS
  LIBRARIES
  DEFINITIONS
  QTMODULES
)
