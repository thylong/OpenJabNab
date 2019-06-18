cmake_minimum_required(VERSION 3.7)
project(plugin_annuaire)

ojnPlugin(NAME ${PROJECT_NAME}
	  FILES plugin_annuaire.h plugin_annuaire.cpp
	  INCLUDE_DIRS
	  LIBRARIES
	  DEFINITIONS
	  QTMODULES
)
