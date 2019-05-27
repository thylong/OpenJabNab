cmake_minimum_required(VERSION 3.7)
project(plugin_stats)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_stats.h plugin_stats.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
