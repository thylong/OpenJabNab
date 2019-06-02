#include <iostream>

#ifndef COMMON_MAIN
  #define CATCH_CONFIG_MAIN
#endif
#include <catch.hpp>

void fun(double d)
{
  std::cout << d << std::endl;

}
TEST_CASE("Hi","[Lib][Translator]")
{
  REQUIRE(true);
  fun(123.4);
}
