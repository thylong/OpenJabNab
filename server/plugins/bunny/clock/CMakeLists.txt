cmake_minimum_required(VERSION 3.7)
project(plugin_clock)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_clock.h plugin_clock.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
