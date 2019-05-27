cmake_minimum_required(VERSION 3.7)
project(plugin_surprise)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_surprise.h plugin_surprise.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
