cmake_minimum_required(VERSION 3.7)
project(plugin_callurl)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_callurl.h plugin_callurl.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
