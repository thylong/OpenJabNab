cmake_minimum_required(VERSION 3.7)
project(OpenJabNab)

set(QT_VERSION 5.7.1)

if(WIN32)
    list(APPEND CMAKE_PREFIX_PATH "C:/Tools/Qt/${QT_VERSION}/mingw53_32/lib/cmake")
    list(APPEND CMAKE_MODULE_PATH "C:/Tools/Qt/${QT_VERSION}/mingw53_32/lib/cmake")
    set(WIN_LIB Ws2_32)
endif()

if(NOT CMAKE_BUILD_TYPE)
  message(STATUS "Setting build type to 'Debug' as none was specified.")
  set(CMAKE_BUILD_TYPE Debug CACHE STRING "Choose the type of build." FORCE)
  # Set the possible values of build type for cmake-gui
  set_property(CACHE CMAKE_BUILD_TYPE PROPERTY STRINGS "Debug" "Release" "MinSizeRel" "RelWithDebInfo")
endif()

set(CMAKE_INCLUDE_CURRENT_DIR ON)
set(CMAKE_AUTOMOC ON)
set(CMAKE_AUTORCC ON)

find_package(Qt5 ${QT_VERSION}
	COMPONENTS Core Network Sql REQUIRED
)

set(CMAKE_RUNTIME_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR}/bin/)
set(CMAKE_LIBRARY_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR}/bin/lib)

if(BUILD_TESTS)
  include(tests/CodeCoverage.cmake)
  APPEND_COVERAGE_COMPILER_FLAGS()
  set(COVERAGE_EXCLUDES '/usr/*'
                          '${PROJECT_SOURCE_DIR}/tests/catch.hpp')

  enable_testing()
endif()

add_subdirectory(lib/)
add_subdirectory(main/)
add_subdirectory(tts/)
add_subdirectory(plugins/)

if(BUILD_TESTS)
  add_subdirectory(tests/)
endif()
