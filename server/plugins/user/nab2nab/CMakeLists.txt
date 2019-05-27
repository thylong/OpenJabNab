cmake_minimum_required(VERSION 3.7)
project(plugin_nab2nab)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_nab2nab.h plugin_nab2nab.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
