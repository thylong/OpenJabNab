cmake_minimum_required(VERSION 3.7)
project(tts_google)

find_package(Qt5 REQUIRED COMPONENTS Core Network)

ojnTTS(NAME ${PROJECT_NAME}
	  FILES tts_google.h tts_google.cpp
	  INCLUDE_DIRS
	  LIBRARIES Qt5::Network
	  DEFINITIONS
	  QTMODULES
)