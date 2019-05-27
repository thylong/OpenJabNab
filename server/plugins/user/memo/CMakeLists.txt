cmake_minimum_required(VERSION 3.7)
project(plugin_memo)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_memo.h plugin_memo.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
