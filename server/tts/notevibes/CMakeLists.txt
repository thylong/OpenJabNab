cmake_minimum_required(VERSION 3.7)
project(tts_notevibes)

ojnTTS(NAME ${PROJECT_NAME}
	  FILES tts_notevibes.h tts_notevibes.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
