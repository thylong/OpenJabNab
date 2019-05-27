cmake_minimum_required(VERSION 3.7)
project(plugin_rss)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_rss.h plugin_rss.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
