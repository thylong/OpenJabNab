cmake_minimum_required(VERSION 3.7)
project(plugin_reset)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_reset.h plugin_reset.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
