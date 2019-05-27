cmake_minimum_required(VERSION 3.7)
project(plugin_earinfo)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_earinfo.h plugin_earinfo.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
