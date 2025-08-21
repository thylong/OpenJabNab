cmake_minimum_required(VERSION 3.7)
project(plugin_chatgpt)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_chatgpt.h plugin_chatgpt.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)