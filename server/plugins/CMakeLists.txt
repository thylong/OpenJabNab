cmake_minimum_required(VERSION 3.7)
project(OpenJabNabPlugins)

function(ojnPlugin)
	cmake_parse_arguments(MY_PLUGIN "" "NAME" "FILES;INCLUDE_DIRS;LIBRARIES;DEFINITIONS;QTMODULES" ${ARGN})
  add_library(${MY_PLUGIN_NAME} SHARED
    ${MY_PLUGIN_FILES}
  )

  target_include_directories(${MY_PLUGIN_NAME}
  PRIVATE
    ${MY_PLUGIN_INCLUDE_DIRS}
    $<TARGET_PROPERTY:ojn,INTERFACE_INCLUDE_DIRECTORIES>
  )

  target_link_libraries(${MY_PLUGIN_NAME}
    ${MY_PLUGIN_LIBRARIES}
    ojn
    ${MY_PLUGIN_QTMODULES}
  )

  target_compile_definitions(${MY_PLUGIN_NAME}
  PRIVATE
    ${MY_PLUGIN_DEFINITIONS}
  )

  set_target_properties(${MY_PLUGIN_NAME}
  PROPERTIES
    LIBRARY_OUTPUT_DIRECTORY ${CMAKE_LIBRARY_OUTPUT_DIRECTORY}/plugins
  )
endfunction()

add_subdirectory(system/)
add_subdirectory(bunny/)
add_subdirectory(user/)
add_subdirectory(tools/)
add_subdirectory(services/)

