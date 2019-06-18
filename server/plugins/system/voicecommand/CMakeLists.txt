cmake_minimum_required(VERSION 3.7)
project(plugin_voicecommand)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_voicecommand.h plugin_voicecommand.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
