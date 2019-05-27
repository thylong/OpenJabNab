cmake_minimum_required(VERSION 3.7)
project(plugin_msgall)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_msgall.h plugin_msgall.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
