cmake_minimum_required(VERSION 3.7)
project(plugin_colorbreathing)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_colorbreathing.h plugin_colorbreathing.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
