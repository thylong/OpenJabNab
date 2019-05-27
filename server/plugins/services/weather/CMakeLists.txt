cmake_minimum_required(VERSION 3.7)
project(plugin_weather)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_weather.h plugin_weather.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
