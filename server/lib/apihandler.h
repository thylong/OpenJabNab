#ifndef _APIHANDLER_H_
#define _APIHANDLER_H_

#include <map>
#include <QString>

#include "global.h"
#include "log.h"
#include "apianswers.h"
#include "httprequest.h"

#include <functional>

class Account;

class PluginApiHandler;
template<class C, typename T> class ApiHandlerGeneric {};
template<class C> class ApiHandler;

template<class C, typename R, typename ...Args>
class ApiHandlerGeneric<C,R(Args...)>
{
  friend class PluginApiHandler;
  friend class ApiHandler<C>;
  
public:
  using APICallMap_t = std::map<QString, std::pair<QStringList, std::function<R(C*,Args...)> > >;

  template<typename cC>
  R processAPICall(cC* obj, const QString& sig, Args&&... args)
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
    return (it->second.second)(obj,std::forward<Args>(args)...);
  }
  /*
  void print(void)
  {
    std::cout << "  [ApiHandlerGeneric::print] "<< std::endl;
    for(const auto& it: _apicalls)
      std::cout << "    - " << it.first << ":" << &it.second << std::endl;
  }*/

  const APICallMap_t& apiCalls = _apicalls;  // For help()

protected:
  template<typename Cf>
  void registerAPICall(const QString& sig, R(Cf::*fn)(Args...))
  {
    QRegExp rx("(.*)\\((.*)\\)");
    if(rx.indexIn(sig) == -1)
    {
      LogError(QString("Invalid Api Signature : %1").arg(sig));
      return;
    }
    QString funcName = rx.cap(1);
    QStringList args = rx.cap(2).split(',', QString::SkipEmptyParts);
    //std::cout << "  [ApiHandlerGeneric::registerAPICall] " << sig << " " << &fn << std::endl;
    //std::cout << "    Put into map... " << &_apicalls << std::endl;
    _apicalls.emplace(funcName, std::make_pair(args,[fn](C* o, Args... args) -> R { 
      //std::cout << "    [ApiHandlerGeneric::trampoline] " << o << " " << &fn << std::endl;
      return (dynamic_cast<Cf*>(o)->*fn)(std::forward<Args>(args)...); 
    }));
  }

private:
  APICallMap_t _apicalls;
};

#define DECLARE_API_CALL(FSIG, FUNC) registerApiCall(FSIG, FUNC)
#define API_CALL(NAME) ApiAnswers::Answer* NAME(HTTPRequest const& hRequest, Account const& account)
using ApiCall_t      = ApiAnswers::Answer*     (HTTPRequest const&,          Account const&);

template<class C>
class ApiHandler
{
public:

  ApiHandler()
  {
    DECLARE_API_CALL("help()",&ApiHandler<C>::Api_help);
  }

  inline static void print(void) { _api.print(); }

  template<typename ...cArgs>
  inline auto ProcessApiCall(cArgs... args)
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
  API_CALL(Api_help)
  {
      QMap<QString,QVariant> ret;
			for (const auto& it: _api.apiCalls)
				ret.insert(it.first,it.second.first.join(','));
			return new ApiAnswers::MappedList(ret);
  }

  static ApiHandlerGeneric<C,ApiCall_t> _api;
};
template<class C> ApiHandlerGeneric<C,ApiCall_t> ApiHandler<C>::_api;

#endif
