cmake_minimum_required(VERSION 3.7)
project(plugin_sleep)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_sleep.h plugin_sleep.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
