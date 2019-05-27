cmake_minimum_required(VERSION 3.7)
project(plugin_choregraphy)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_choregraphy.h plugin_choregraphy.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
