cmake_minimum_required(VERSION 3.7)
project(tts_acapela)

ojnTTS(NAME ${PROJECT_NAME}
	  FILES tts_acapela.h tts_acapela.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
