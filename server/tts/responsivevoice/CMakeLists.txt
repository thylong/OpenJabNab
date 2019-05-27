cmake_minimum_required(VERSION 3.7)
project(tts_responsivevoice)

ojnTTS(NAME ${PROJECT_NAME}
	  FILES tts_responsivevoice.h tts_responsivevoice.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
