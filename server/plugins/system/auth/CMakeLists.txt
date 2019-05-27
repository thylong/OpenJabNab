cmake_minimum_required(VERSION 3.7)
project(plugin_auth)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_auth.h plugin_auth.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
