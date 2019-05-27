cmake_minimum_required(VERSION 3.7)
project(plugin_needtoknow)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_needtoknow.h plugin_needtoknow.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
