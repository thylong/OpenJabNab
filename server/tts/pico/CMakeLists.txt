cmake_minimum_required(VERSION 3.7)
project(tts_pico)

ojnTTS(NAME ${PROJECT_NAME}
	  FILES tts_pico.h tts_pico.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
