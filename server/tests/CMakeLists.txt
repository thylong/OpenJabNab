cmake_minimum_required(VERSION 3.7)
project(OpenJabNabTests)

function(add_test_target)

  cmake_parse_arguments(
      ARG # prefix of output variables
      "ALL" # list of names of the boolean arguments (only defined ones will be true)
      "NAME" # list of names of mono-valued arguments
      "SRCS;INCLUDE_DIRS;LIBRARIES;DEFINES" # list of names of multi-valued arguments (output variables are lists)
      ${ARGN} # arguments of the function to parse, here we take the all original ones
  )
  # note: if it remains unparsed arguments, here, they can be found in variable ARG_UNPARSED_ARGUMENTS

  message("[+] Add Test ${ARG_NAME}")

  #message("Provided sources are:")
  #foreach(src ${PARSED_ARGS_SRCS})
  #    message("- ${src}")
  #endforeach()

  if(${ARG_ALL})
    set(ARG_SRCS ${ARG_SRCS} ${all_srcs})
    set(ARG_DEFINES COMMON_MAIN ${ARG_DEFINES} ${all_defs})
    set(ARG_INCLUDE_DIRS ${ARG_INCLUDE_DIRS} ${all_incs})
    set(ARG_LIBRARIES ${ARG_LIBRARIES} ${all_libs})
  else()
    set(all_srcs ${all_srcs} ${ARG_SRCS} PARENT_SCOPE)
    set(all_defs ${all_defs} ${ARG_DEFINES} PARENT_SCOPE)
    set(all_incs ${all_incs} ${ARG_INCLUDE_DIRS} PARENT_SCOPE)
    set(all_libs ${all_libs} ${ARG_LIBRARIES} PARENT_SCOPE)
    set(all_tests ${all_tests} test_${ARG_NAME} PARENT_SCOPE)
  endif()

  add_executable(test_${ARG_NAME}
    ${ARG_SRCS}
  )

  target_compile_definitions(test_${ARG_NAME}
  PUBLIC
    ${ARG_DEFINES}
  )

  target_include_directories(test_${ARG_NAME}
  PUBLIC
      $<BUILD_INTERFACE:
          ${CMAKE_CURRENT_SOURCE_DIR}/catch
          $<TARGET_PROPERTY:ojn,INTERFACE_INCLUDE_DIRECTORIES>
          ${ARG_INCLUDE_DIRS}
      >
  )

  target_link_libraries(test_${ARG_NAME}
  PUBLIC
    ${ARG_LIBRARIES}
  )

  set_target_properties(test_${ARG_NAME}
  PROPERTIES
    RUNTIME_OUTPUT_DIRECTORY ${CMAKE_RUNTIME_OUTPUT_DIRECTORY}/tests
    COMPILE_FLAGS -fPIC
  )

  setup_target_for_coverage(
    NAME cov_${ARG_NAME}
    EXECUTABLE ${CMAKE_RUNTIME_OUTPUT_DIRECTORY}/tests/test_${ARG_NAME} -r compact
    DEPENDENCIES test_${ARG_NAME}
    WORKING_DIRECTORY ${CMAKE_RUNTIME_OUTPUT_DIRECTORY}/
  )

  if(NOT ${ARG_ALL})
    add_test(run_test_${ARG_NAME}  ${CMAKE_RUNTIME_OUTPUT_DIRECTORY}/tests/test_${ARG_NAME} )
  endif()

  add_custom_target(run_test_${ARG_NAME}
    COMMAND ${CMAKE_RUNTIME_OUTPUT_DIRECTORY}/tests/test_${ARG_NAME} -r compact #-s
    DEPENDS test_${ARG_NAME}
    WORKING_DIRECTORY ${PROJECT_SOURCE_DIR}
  )
endfunction(add_test_target)

add_test_target(NAME api          SRCS  lib/api.cpp                              )
add_test_target(NAME translator   SRCS  lib/translator.cpp          LIBRARIES ojn)
add_test_target(NAME v1_msg       SRCS  lib/v1/msg.cpp              LIBRARIES ojn)

  # A ll
add_test_target(NAME All          SRCS  test_all.cpp                 ALL)

add_custom_target(check COMMAND ${CMAKE_CTEST_COMMAND} DEPENDS ${all_tests})

