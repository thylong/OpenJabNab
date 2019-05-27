cmake_minimum_required(VERSION 3.7)
project(plugin_webradio)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_webradio.h plugin_webradio.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
