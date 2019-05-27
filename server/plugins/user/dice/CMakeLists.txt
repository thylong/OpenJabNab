cmake_minimum_required(VERSION 3.7)
project(plugin_dice)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_dice.h plugin_dice.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
