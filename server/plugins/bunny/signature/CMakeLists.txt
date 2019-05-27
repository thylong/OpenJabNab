cmake_minimum_required(VERSION 3.7)
project(plugin_signature)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_signature.h plugin_signature.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
