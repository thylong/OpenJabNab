cmake_minimum_required(VERSION 3.7)
project(plugin_taichi)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_taichi.h plugin_taichi.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
