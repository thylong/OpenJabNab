cmake_minimum_required(VERSION 3.7)
project(OpenJabNabTTSs)

function(ojnTTS)
	cmake_parse_arguments(MY_PLUGIN "" "NAME" "FILES;INCLUDE_DIRS;LIBRARIES;DEFINITIONS;QTMODULES" ${ARGN})
  add_library(${MY_PLUGIN_NAME} SHARED
    ${MY_PLUGIN_FILES}
  )
  QT5_USE_MODULES(${MY_PLUGIN_NAME} Core Network ${MY_PLUGIN_QTMODULES})

  target_include_directories(${MY_PLUGIN_NAME}
  PRIVATE
    ${MY_PLUGIN_INCLUDE_DIRS}
    $<TARGET_PROPERTY:ojn,INTERFACE_INCLUDE_DIRECTORIES>
  )

  target_link_libraries(${MY_PLUGIN_NAME}
    ${MY_PLUGIN_LIBRARIES}
    ojn
  )

  target_compile_definitions(${MY_PLUGIN_NAME}
  PRIVATE
    ${MY_PLUGIN_DEFINITIONS}
  )

  set_target_properties(${MY_PLUGIN_NAME}
  PROPERTIES
    LIBRARY_OUTPUT_DIRECTORY ${CMAKE_LIBRARY_OUTPUT_DIRECTORY}/tts
  )
endfunction()

add_subdirectory(acapela/)
add_subdirectory(bluemix/)
add_subdirectory(notevibes/)
add_subdirectory(pico/)
add_subdirectory(responsivevoice/)
