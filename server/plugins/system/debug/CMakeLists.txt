cmake_minimum_required(VERSION 3.7)
project(plugin_debug)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_debug.h plugin_debug.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
