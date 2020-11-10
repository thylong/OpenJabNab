cmake_minimum_required(VERSION 3.7)
project(plugin_volume)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_volume.h plugin_volume.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
