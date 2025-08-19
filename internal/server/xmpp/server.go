package xmpp

import (
	"bufio"
    "encoding/base64"
    "fmt"
	"io"
	"log/slog"
	"net"
	"time"
    "sync"
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
    OnButton func(id string, clicks int)
    OnEars   func(id string, left, right int)
    OnRFID   func(id string, tag string)
    Dump func(cat string, data []byte)
    BypassAuth bool
    ReadTimeout time.Duration
    OnRegistered func(id, resource string)

    mu    sync.RWMutex
    conns map[string]net.Conn      // bunnyID -> conn
    res   map[string]string        // bunnyID -> resource
    msgSeq uint64                  // message sequence for unique ids
}

func (s *Server) ListenAndServe(stop <-chan struct{}) error {
    if s.conns == nil { s.conns = make(map[string]net.Conn); s.res = make(map[string]string) }
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
    // Use a rolling read deadline to keep long-lived XMPP sessions alive
	br := bufio.NewReader(c)
    h := newHandler(s.Domain, s.Logger)
    h.onIdentify = func(id string) { if s.OnConnect != nil { s.OnConnect(id) } }
    if s.GetPassword != nil { h.getPassword = s.GetPassword }
    if s.NonceFactory != nil { h.nonceFactory = s.NonceFactory }
    if s.OnButton != nil { h.onButton = s.OnButton }
    if s.OnEars != nil { h.onEars = s.OnEars }
    if s.OnRFID != nil { h.onRFID = s.OnRFID }
    h.bypassAuth = s.BypassAuth
    h.onRegistered = func(id, resource string) {
        if s.OnRegistered != nil { s.OnRegistered(id, resource) }
        s.mu.Lock(); s.conns[id] = c; s.res[id] = resource; s.mu.Unlock()
    }
    buf := make([]byte, 4096)
	for {
        // Refresh read deadline before each blocking read
        d := s.ReadTimeout
        if d <= 0 { d = 2 * time.Minute }
        _ = c.SetReadDeadline(time.Now().Add(d))
        n, err := br.Read(buf)
    	if err != nil {
			if err == io.EOF { return }
			s.Logger.Warn("xmpp read error", slog.String("err", err.Error()))
			return
		}
        if s.Dump != nil { s.Dump("XMPP Bunny", buf[:n]) }
        out := h.Process(buf[:n])
		for _, resp := range out {
            if _, err := c.Write([]byte(resp)); err != nil {
				s.Logger.Warn("xmpp write error", slog.String("err", err.Error()))
				return
			}
            if s.Dump != nil { s.Dump("XMPP To Bunny", []byte(resp)) }
		}
	}
    if s.OnDisconnect != nil { s.OnDisconnect(h.getID()) }
    // Clean mapping
    s.mu.Lock(); delete(s.conns, h.getID()); delete(s.res, h.getID()); s.mu.Unlock()
}

// SendPacket composes and sends a message stanza with base64 payload to the bunny if connected.
func (s *Server) SendPacket(bunnyID string, payload []byte) bool {
    s.mu.RLock(); c, ok := s.conns[bunnyID]; resource := s.res[bunnyID]; s.mu.RUnlock()
    if !ok || resource == "" { return false }
    // Compose message similar to C++ handler
    dom := s.Domain
    b64 := base64.StdEncoding.EncodeToString(payload)
    // Match original server: no type attr; sender net.openjabnab.platform@<domain>/services; unique id per message
    s.mu.Lock(); s.msgSeq++; id := s.msgSeq; s.mu.Unlock()
    msg := "<message from='net.openjabnab.platform@" + dom + "/services' to='" + bunnyID + "@" + dom + "/" + resource + "' id='OJaNa-" + fmt.Sprintf("%d", id) + "'>" +
        "<packet xmlns='violet:packet' format='0.9' ttl='604800'>" + b64 + "</packet></message>"
    if _, err := c.Write([]byte(msg)); err != nil { s.Logger.Warn("xmpp send error", slog.String("err", err.Error())) ; return false }
    if s.Dump != nil { s.Dump("XMPP To Bunny", []byte(msg)) }
    return true
}
