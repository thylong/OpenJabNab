cmake_minimum_required(VERSION 3.7)
project(plugin_status)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_status.h plugin_status.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
