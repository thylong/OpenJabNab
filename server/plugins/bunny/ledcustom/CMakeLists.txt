cmake_minimum_required(VERSION 3.7)
project(plugin_ledcustom)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_ledcustom.h plugin_ledcustom.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
