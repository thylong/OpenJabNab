cmake_minimum_required(VERSION 3.7)
project(plugin_rfid)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_rfid.h plugin_rfid.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
