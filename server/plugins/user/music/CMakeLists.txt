cmake_minimum_required(VERSION 3.7)
project(plugin_music)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_music.h plugin_music.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
