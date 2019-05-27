cmake_minimum_required(VERSION 3.7)
project(plugin_ears)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_ears.h plugin_ears.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
