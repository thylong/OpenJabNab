package xmpp

import (
	"bufio"
	"io"
	"log/slog"
	"net"
	"time"
)

type Server struct {
	Addr   string // host:port
	Domain string
	Logger *slog.Logger
    // TODO: Inject account/bunny managers to verify auth and track connections
    OnConnect func(id string)
    OnDisconnect func(id string)
    GetPassword func(user string) (string, bool)
    OnListen func(addr string)
    NonceFactory func() string
}

func (s *Server) ListenAndServe(stop <-chan struct{}) error {
	ln, err := net.Listen("tcp", s.Addr)
    if err != nil { return err }
    if s.OnListen != nil { s.OnListen(ln.Addr().String()) }
	defer ln.Close()
	done := make(chan struct{})
	go func() { <-stop; _ = ln.Close(); close(done) }()
	for {
		c, err := ln.Accept()
		if err != nil {
			select { case <-done: return nil; default: }
			if ne, ok := err.(net.Error); ok && ne.Temporary() { continue }
			return err
		}
		go s.handleConn(c)
	}
}

func (s *Server) handleConn(c net.Conn) {
	defer c.Close()
	_ = c.SetDeadline(time.Now().Add(60 * time.Second))
	br := bufio.NewReader(c)
    h := newHandler(s.Domain, s.Logger)
    h.onIdentify = func(id string) { if s.OnConnect != nil { s.OnConnect(id) } }
    if s.GetPassword != nil { h.getPassword = s.GetPassword }
    if s.NonceFactory != nil { h.nonceFactory = s.NonceFactory }
	buf := make([]byte, 4096)
	for {
		n, err := br.Read(buf)
    	if err != nil {
			if err == io.EOF { return }
			s.Logger.Warn("xmpp read error", slog.String("err", err.Error()))
			return
		}
		out := h.Process(buf[:n])
		for _, resp := range out {
			if _, err := c.Write([]byte(resp)); err != nil {
				s.Logger.Warn("xmpp write error", slog.String("err", err.Error()))
				return
			}
		}
	}
    if s.OnDisconnect != nil { s.OnDisconnect(h.getID()) }
}
