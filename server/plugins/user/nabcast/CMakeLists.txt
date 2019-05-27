cmake_minimum_required(VERSION 3.7)
project(plugin_nabcast)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_nabcast.h plugin_nabcast.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
