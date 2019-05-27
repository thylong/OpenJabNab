cmake_minimum_required(VERSION 3.7)
project(plugin_locate)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_locate.h plugin_locate.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
