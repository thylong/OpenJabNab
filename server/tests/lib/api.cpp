#include <iostream>

#ifndef COMMON_MAIN
  #define CATCH_CONFIG_MAIN
#endif
#include <catch.hpp>

#include <map>
#include <functional>

/*****************************************************************************
 *****************************************************************************/
class PluginApiHandler;
template<class C, typename T> class ApiHandlerGeneric {};
template<class C> class ApiHandler;

template<class C, typename R, typename ...Args>
class ApiHandlerGeneric<C,R(Args...)>
{
  friend class PluginApiHandler;
  friend class ApiHandler<C>;
  
public:
  using APICallMap_t = std::map<std::string, std::function<R(C*,Args...)> >;

  template<typename cC>
  R processAPICall(cC* obj, const std::string& sig, Args&&... args)
  {
    //std::cout << "  [ApiHandlerGeneric::processAPICall] " << sig << std::endl;
    //std::cout << "    Look into map... " << &_apicalls << std::endl;
    const auto& it = _apicalls.find(sig);
    if(it == _apicalls.end())
    {
      //std::cout << "    No such APICall: " << sig << std::endl;
      return R{};
    }
    //std::cout << "    Calling trampoline... " << std::endl;
    return (it->second)(obj,std::forward<Args>(args)...);
  }

  void print(void)
  {
    std::cout << "  [ApiHandlerGeneric::print] "<< std::endl;
    for(const auto& it: _apicalls)
      std::cout << "    - " << it.first << ":" << &it.second << std::endl;
  }

  const APICallMap_t& apiCalls = _apicalls;  // For help()

protected:
  template<typename Cf>
  void registerAPICall(const std::string& sig, R(Cf::*fn)(Args...))
  {
    //std::cout << "  [ApiHandlerGeneric::registerAPICall] " << sig << " " << &fn << std::endl;
    //std::cout << "    Put into map... " << &_apicalls << std::endl;
    _apicalls.emplace(sig,[fn](C* o, Args... args) -> R { 
      //std::cout << "    [ApiHandlerGeneric::trampoline] " << o << " " << &fn << std::endl;
      return (dynamic_cast<Cf*>(o)->*fn)(std::forward<Args>(args)...); 
    });
  }

private:
  APICallMap_t _apicalls;
};

/*****************************************************************************
 *****************************************************************************/
#define DECLARE_API_CALL(FSIG, FUNC) registerApiCall(FSIG, FUNC)
#define API_CALL(NAME) bool NAME(double b, bool a)
using ApiCall_t      = bool     (double  , bool  );

template<class C>
class ApiHandler
{
public:
  inline static void print(void) { _api.print(); }

  template<typename ...cArgs>
  inline auto processAPICall(cArgs&&... args)
  {
    //auto* obj = dynamic_cast<C*>(this);
    //std::cout << "    Casting pointer..." << this << " => " << obj << std::endl;
    return _api.processAPICall(dynamic_cast<C*>(this),std::forward<cArgs>(args)...);
  }

protected:
  template<typename ...rArgs>
  inline static auto registerApiCall(rArgs&&... args)
  {
    return _api.registerAPICall(std::forward<rArgs>(args)...);
  }

  virtual ~ApiHandler() = default;  // for polymorphism
private:
  static ApiHandlerGeneric<C,ApiCall_t> _api;
};
template<class C> ApiHandlerGeneric<C,ApiCall_t> ApiHandler<C>::_api;


#define DECLARE_PLUGIN_API_CALL(FSIG, FUNC) registerPluginApiCall(_pluginApi, FSIG, FUNC)
#define PLUGIN_API_CALL(NAME) bool NAME(double b, bool a)
using PluginApiCall_t           = bool     (double  , bool  );

#define DECLARE_PLUGIN_BUNNY_API_CALL(FSIG, FUNC) registerPluginApiCall(_bunnyApi, FSIG, FUNC)
#define PLUGIN_BUNNY_API_CALL(NAME) bool NAME(double b, bool a)
using PluginBunnyApiCall_t            = bool(double  , bool  );

#define DECLARE_PLUGIN_ZTAMP_API_CALL(FSIG, FUNC) registerPluginApiCall(_ztampApi, FSIG, FUNC)
#define PLUGIN_ZTAMP_API_CALL(NAME) bool NAME(double b, bool a)
using PluginZtampApiCall_t            = bool(double  , bool  );

class PluginApiHandler
{
public:
  void print(void)
  {
    std::cout << "[PluginApiHandler::print]" << std::endl;
    std::cout << "  PluginAPI" << std::endl;
    _pluginApi.print();
    std::cout << "  BunnyAPI" << std::endl;
    _bunnyApi.print();
    std::cout << "  ZtampAPI" << std::endl;
    _ztampApi.print();
  }

  template<typename...cArgs>
  inline auto processPluginAPICall(cArgs&&... args)
  {
    return _pluginApi.processAPICall(this, std::forward<cArgs>(args)...);
  }

  template<typename...cArgs>
  inline auto processBunnyAPICall(cArgs&&... args)
  {
    return _bunnyApi.processAPICall(this, std::forward<cArgs>(args)...);
  }

  template<typename...cArgs>
  inline auto processZtampAPICall(cArgs&&... args)
  {
    return _ztampApi.processAPICall(this, std::forward<cArgs>(args)...);
  }

protected:
  template<typename T, typename ...Args>
  void registerPluginApiCall(ApiHandlerGeneric<PluginApiHandler, T>& apiList, Args... args)
  {
    apiList.registerAPICall(std::forward<Args>(args)...);
  }
  ApiHandlerGeneric<PluginApiHandler, PluginApiCall_t> _pluginApi;
  ApiHandlerGeneric<PluginApiHandler, PluginBunnyApiCall_t> _bunnyApi;
  ApiHandlerGeneric<PluginApiHandler, PluginZtampApiCall_t> _ztampApi;

  virtual ~PluginApiHandler() = default; // for polymorphism
};

/*****************************************************************************
 *****************************************************************************/
class apiStaticManager
  : public ApiHandler<apiStaticManager>
{
private:
  apiStaticManager()
  {
    //std::cout << "[apiStaticManager::ctor] " << this << std::endl;
  }
public:
  static void InitApiCalls()
  {
    std::cout << "[apiStaticManager::InitApiCalls] " << std::endl;
    DECLARE_API_CALL("myApiCall", &apiStaticManager::Api_myApiCall);
  }

  static auto& Instance(void)
  {
    static apiStaticManager _instance;
    //std::cout << "[apiStaticManager::Instance] " << &_instance << std::endl;
    return _instance;
  }
private:
  API_CALL(Api_myApiCall);  
};

API_CALL(apiStaticManager::Api_myApiCall)
{
  std::cout << "[apiStaticManager::Api_myApiCall] " << this << std::endl;
  //std::cout << "Call site " << this << std::endl;
  return true;
}

TEST_CASE("Manager","[Lib][API]")
{
  apiStaticManager::InitApiCalls();
  apiStaticManager::print();
  auto& api = apiStaticManager::Instance();
  REQUIRE(api.processAPICall("myApiCall", 12.0, true));
}
/*****************************************************************************
 *****************************************************************************/

class myPlugin
  : public PluginApiHandler
{
public:
  void InitApiCalls(void)
  {
    DECLARE_PLUGIN_API_CALL      ("myPluginCall", &myPlugin::myPluginCall);
    DECLARE_PLUGIN_BUNNY_API_CALL("myBunnyCall",  &myPlugin::myBunnyCall);
    DECLARE_PLUGIN_ZTAMP_API_CALL("myZtampCall",  &myPlugin::myZtampCall);
  }

private:
  PLUGIN_API_CALL(myPluginCall)
  {
    std::cout << "      [myPlugin::myPluginCall] " << this << std::endl;
    return true;
  }
  PLUGIN_BUNNY_API_CALL(myBunnyCall)
  {
    std::cout << "      [myPlugin::myBunnyCall] " << this << std::endl;
    return true;
  }
  PLUGIN_ZTAMP_API_CALL(myZtampCall)
  {
    std::cout << "      [myPlugin::myZtampCall] " << this << std::endl;
    return true;
  }
};

TEST_CASE("Plugin","[Lib][API]")
{
  myPlugin p;
  p.InitApiCalls();
  p.print();

  REQUIRE(p.processPluginAPICall("myPluginCall", 34.0, false));
  REQUIRE(p.processBunnyAPICall ("myBunnyCall" , 56,   78.9 ));
  REQUIRE(p.processZtampAPICall ("myZtampCall" , 12,   34.5));
}