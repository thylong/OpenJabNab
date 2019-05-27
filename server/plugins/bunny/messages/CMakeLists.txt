cmake_minimum_required(VERSION 3.7)
project(plugin_messages)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_messages.h plugin_messages.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
