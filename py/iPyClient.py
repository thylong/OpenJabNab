#!/usr/bin/env python3

from IPython.terminal.embed import InteractiveShellEmbed
from traitlets.config.loader import Config

#from urllib import request
import socket,struct

_Host='localhost'
_Port=8081
_User='admin'
_Pwd='admin'
_Token=None

def get(url, *args,**kwargs):
  rep = ""
  req = ''
  #req = 'X-FORWARDED-FOR: 0.0.0.0'+"\n"
  req += 'USER-AGENT: OJN Admin'+"\n"
  req += 'CONNECTION: close'+"\n"
  req += 'HOST: '+_Host+"\n"
  req += '\00'
  req += url
  
  req_len = 5 + len(req)
  req_type = 1

  packed_req = struct.pack('IB{}s'.format(len(req)),req_len,req_type,bytes(req,'ascii'))
  #print(len(packed_req))
  #print(packed_req)
  with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
    s.connect((_Host, _Port))
    s.send(packed_req)
    while True:
      t = s.recv(128)
      #print(t)
      if t == b'':
          break
      rep += t.decode('ascii')
  return rep

import xml.dom.minidom as xmldom
def apiget(url,sendToken=True):
    r = '/ojn_api'+('/' if url[0] != '/' else '')+url
    global _Token
    if sendToken:
        if _Token is None:
            login()
        if _Token is None:
            return None
        r += ('&' if '?' in r else '?')+'token='+_Token
    rx = get(r)
    if len(rx) == 0:
        return None
    # Parse XML answer
    rs = xmldom.parseString(rx)
    rs = rs.childNodes
    if len(rs) != 1 or rs[0].localName != 'api':
        return None
    api = rs[0].childNodes
    if len(api) != 1:
        return None
    api = api[0]
    if api.localName == 'value' or api.localName == 'error' or api.localName == 'ok':
#       print('APIValue')
        api = api.childNodes
        if len(api) == 1 and api[0].nodeType == api[0].TEXT_NODE:
            return api[0].data
        else:
            return ''
    elif api.localName == 'list':
        #print('APIList')
        api = api.childNodes
        out = []
        mappedList = False
        for it in api:
            if it.localName != 'item':
                continue
            if len(it.childNodes) == 1: # Simple list
                k = it.childNodes[0].data
                out.append(k)
            elif len(it.childNodes) == 2: # Mapped list
                k = it.getElementsByTagName('key')[0]
                if len(k.childNodes) == 1 and k.childNodes[0].nodeType == k.childNodes[0].TEXT_NODE:
                  k = k.childNodes[0].data
                else:
                  k = ''
                v = it.getElementsByTagName('value')[0]
                if len(v.childNodes) == 1 and v.childNodes[0].nodeType == v.childNodes[0].TEXT_NODE:
                  v = v.childNodes[0].data
                else:
                  v = ''
                out.append((k,v))
                mappedList = True
        return dict(out) if mappedList else out
    return None

def saveAccounts():
#    login()
    return apiget('/accounts/saveAccounts')

def about():
  return apiget('/global/about',False)

def login(login=_User, pwd=_Pwd):
  global _Token
  _Token = apiget('/accounts/auth?login='+login+'&pass='+pwd,False)
  if len(_Token) == 0:
    _Token = None
    return False
  return True

def shell():
  ipcfg = Config()
  ipcfg.InteractiveShell.confirm_exit = False
  #ipcfg.using = 'asyncio'
  ipshell = InteractiveShellEmbed(config=ipcfg, banner1 = 'Welcome to OJN interactive shell')
  ipshell() 

import sys
if __name__ == "__main__":
  if len(sys.argv) > 1:
    functions = locals()
    #print(functions)
    if sys.argv[1] in functions:
      print(functions[sys.argv[1]]())
  else:
#   login()
   shell()

