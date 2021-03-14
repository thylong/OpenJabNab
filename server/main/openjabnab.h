#ifndef _OPENJABNAB_H_
#define _OPENJABNAB_H_

#include <list>

#include <QCoreApplication>
#include <QTimer>
#include "apimanager.h"
#include "pluginmanager.h"

class QTcpServer;
class HttpHandler;
class XmppHandler;

class OpenJabNab 
  : public QCoreApplication
{
  Q_OBJECT

public:
  OpenJabNab(int argc, char ** argv);
  void Close();
  virtual ~OpenJabNab();

signals:
  void Quit();

private:
  QTimer autoSaveTmr,
         nabStatusTmr,
         timeoutTmr;
  void insertServerInDb();

  QTcpServer *httpListener,
             *xmppListener;
  std::list<HttpHandler*> _httpHandlers;
  std::list<XmppHandler*> _xmppHandlers;
};

#endif
