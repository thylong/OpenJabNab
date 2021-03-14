find_package(Git REQUIRED)

set(GIT_DIR -C ${CMAKE_CURRENT_SOURCE_DIR}/)

execute_process(
  COMMAND ${GIT_EXECUTABLE} ${GIT_DIR} describe --exclude=* --dirty=+ --always 
#log --pretty=format:'%h' -n 1
  OUTPUT_VARIABLE GIT_REV
  ERROR_QUIET
)

# Check whether we got any revision (which isn't
# always the case, e.g. when someone downloaded a zip
# file from Github instead of a checkout)
if ("${GIT_REV}" STREQUAL "")
  set(GIT_REV "N/A")
  set(GIT_TAG "N/A")
  set(GIT_BRANCH "N/A")
else()
  execute_process(
    COMMAND ${GIT_EXECUTABLE} ${GIT_DIR} describe --exact-match --tags
    OUTPUT_VARIABLE GIT_TAG 
    ERROR_QUIET
  )
  execute_process(
    COMMAND ${GIT_EXECUTABLE} ${GIT_DIR} rev-parse --abbrev-ref HEAD
    OUTPUT_VARIABLE GIT_BRANCH
    ERROR_QUIET
  )

  string(STRIP "${GIT_REV}" GIT_REV)
  string(STRIP "${GIT_TAG}" GIT_TAG)
  string(STRIP "${GIT_BRANCH}" GIT_BRANCH)
endif()

if(EXISTS ${CMAKE_CURRENT_BINARY_DIR}/version.c)
  file(READ ${CMAKE_CURRENT_BINARY_DIR}/version.c VERSION_FILE)
  string(FIND "${VERSION_FILE}" "\"${GIT_REV}\"" VERSION_)
else()
  set(VERSION_ -1)
endif()

if (VERSION_ EQUAL -1)
  message(STATUS "[*] Generating version.c from Git (${GIT_REV})")
  configure_file( 
    ${CMAKE_CURRENT_SOURCE_DIR}/version.c.in
    ${CMAKE_CURRENT_BINARY_DIR}/version.c
    @ONLY
  )
endif()