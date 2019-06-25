cmake_minimum_required(VERSION 3.7)
project(plugin_lafontaine)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES ${PROJECT_NAME}.h ${PROJECT_NAME}.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
