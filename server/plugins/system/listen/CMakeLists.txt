cmake_minimum_required(VERSION 3.7)
project(plugin_listen)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_listen.h plugin_listen.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
