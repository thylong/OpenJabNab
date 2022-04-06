cmake_minimum_required(VERSION 3.7)
project(openjabnab)
if(WIN32)
	set(WINLIB Ws2_32)
endif()

add_executable(${PROJECT_NAME}
  main.cpp

  openjabnab.h
  openjabnab.cpp
)

add_dependencies(${PROJECT_NAME} ojn_translations)

target_link_libraries(${PROJECT_NAME}
  ${WINLIB}
  ojn
)

target_include_directories(${PROJECT_NAME}
PRIVATE
	${CMAKE_CURRENT_DIRECTORY}
	$<TARGET_PROPERTY:ojn,INTERFACE_INCLUDE_DIRECTORIES>
)
