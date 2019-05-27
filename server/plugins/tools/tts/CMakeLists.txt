cmake_minimum_required(VERSION 3.7)
project(plugin_tts)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_tts.h plugin_tts.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
