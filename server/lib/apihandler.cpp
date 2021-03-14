#include "account.h"

ApiAnswers::Answer* genericHandlers(Account const& account, QString const& request, HTTPRequest const& hRequest)
{
  if(request == "help" && account.IsAdmin())
  {
    QMap<QString,QVariant> ret;
    typename ApiCallsMap<T>::iterator it;
    for (it = apiCalls.begin(); it != apiCalls.end(); ++it)
      ret.insert(it.key(),it.value().second.join(','));
    return new ApiAnswers::MappedList(ret);
  }
  return nullptr;
}