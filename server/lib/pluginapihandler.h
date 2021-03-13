#ifndef _PLUGINAPIHANDLER_H_
#define _PLUGINAPIHANDLER_H_

#include "apihandler.h"

class Account; 
class Bunny;
class Ztamp;
class HTTPRequest;

#define DECLARE_PLUGIN_API_CALL(FSIG, FUNC) registerPluginApiCall(_pluginApi, FSIG, FUNC)
#define PLUGIN_API_CALL(NAME) ApiManager::ApiAnswer* NAME(HTTPRequest const& hRequest, Account const& account)
using PluginApiCall_t       = ApiManager::ApiAnswer*     (HTTPRequest const&, Account const&);

#define DECLARE_PLUGIN_BUNNY_API_CALL(FSIG, FUNC) registerPluginApiCall(_bunnyApi, FSIG, FUNC)
#define PLUGIN_BUNNY_API_CALL(NAME) ApiManager::ApiAnswer* NAME(HTTPRequest const& hRequest, Account const& account, Bunny* bunny)
using PluginBunnyApiCall_t        = ApiManager::ApiAnswer*     (HTTPRequest const&, Account const&, Bunny*);

#define DECLARE_PLUGIN_ZTAMP_API_CALL(FSIG, FUNC) registerPluginApiCall(_ztampApi, FSIG, FUNC)
#define PLUGIN_ZTAMP_API_CALL(NAME) ApiManager::ApiAnswer* NAME(HTTPRequest const& hRequest, Account const& account, Ztamp* ztamp)
using PluginZtampApiCall_t        = ApiManager::ApiAnswer*     (HTTPRequest const&, Account const&, Ztamp*);

class PluginApiHandler
{
public:
  PluginApiHandler()
  {
    DECLARE_PLUGIN_API_CALL      ("help()",&PluginApiHandler::Api_helpPlugin);
    DECLARE_PLUGIN_BUNNY_API_CALL("help()",&PluginApiHandler::Api_helpBunny);
    DECLARE_PLUGIN_ZTAMP_API_CALL("help()",&PluginApiHandler::Api_helpZtamp);
  }

  virtual void InitApiCalls(void) { };
  /*
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
  */

  template<typename...cArgs>
  inline auto ProcessApiCall(cArgs... args)
  {
    return _pluginApi.processAPICall(this, std::forward<cArgs>(args)...);
  }

  template<typename...cArgs>
  inline auto ProcessBunnyApiCall(cArgs... args)
  {
    return _bunnyApi.processAPICall(this, std::forward<cArgs>(args)...);
  }

  template<typename...cArgs>
  inline auto ProcessZtampApiCall(cArgs... args)
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
private:
  PLUGIN_API_CALL(Api_helpPlugin)
  {
    QMap<QString,QVariant> ret;
    for (const auto& it: _pluginApi.apiCalls)
      ret.insert(it.first,it.second.first.join(','));
    return new ApiManager::ApiMappedList(ret);
  }
  PLUGIN_BUNNY_API_CALL(Api_helpBunny)
  {
    QMap<QString,QVariant> ret;
    for (const auto& it: _bunnyApi.apiCalls)
      ret.insert(it.first,it.second.first.join(','));
    return new ApiManager::ApiMappedList(ret);
  }
  PLUGIN_ZTAMP_API_CALL(Api_helpZtamp)
  {
    QMap<QString,QVariant> ret;
    for (const auto& it: _ztampApi.apiCalls)
      ret.insert(it.first,it.second.first.join(','));
    return new ApiManager::ApiMappedList(ret);
  }
};
#endif