cmake_minimum_required(VERSION 3.7)
project(plugin_packet)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_packet.h plugin_packet.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
