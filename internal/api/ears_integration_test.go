package api_test

import (
	"bufio"
	"bytes"
	"encoding/base64"
	"encoding/binary"
	"net"
	"testing"
	"time"

	"log/slog"

	api "OpenJabNab/internal/api"
	plugman "OpenJabNab/internal/plugin"
	plugears "OpenJabNab/internal/plugins/ears"
	httpb "OpenJabNab/internal/server/httpbridge"
	xmpp "OpenJabNab/internal/server/xmpp"
)

func frameGET2(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestEarsSet_SendsAmbientPacketOverXMPP(t *testing.T) {
	// Start XMPP server with bypass auth
	stop := make(chan struct{})
	var addr string
    xs := &xmpp.Server{Addr: "127.0.0.1:0", Domain: "example.com", Logger: slog.Default(), OnListen: func(a string){ addr = a }}
    xs.GetPassword = func(u string) (string, bool) { if u=="user" { return "pass", true }; return "", false }
	go func(){ _ = xs.ListenAndServe(stop) }()
	defer close(stop)
	deadline := time.Now().Add(2*time.Second)
	for addr == "" && time.Now().Before(deadline) { time.Sleep(10*time.Millisecond) }
	if addr == "" { t.Fatal("xmpp server did not start") }

	// Connect bunny client and bind resource 'streaming'
	c, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	defer c.Close()
	bw := bufio.NewWriter(c)
	br := bufio.NewReader(c)
	// open stream
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='example.com' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	buf := make([]byte, 4096)
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
    // SASL PLAIN auth for user/pass
    payload := []byte{0x00}
    payload = append(payload, []byte("user")...)
    payload = append(payload, 0x00)
    payload = append(payload, []byte("pass")...)
    _, _ = bw.WriteString("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" + base64.StdEncoding.EncodeToString(payload) + "</auth>")
    _ = bw.Flush()
    c.SetReadDeadline(time.Now().Add(1*time.Second))
    if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// second stream
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='example.com' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// bind resource
	_, _ = bw.WriteString("<iq type='set' id='1' to='user@example.com'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>streaming</resource></bind></iq>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }

	// Build plugin manager with ears plugin, injecting SendPacket
	pm := plugman.NewManager()
	plug := plugears.New()
	plug.SetPacketSender(xs.SendPacket)
	pm.Register(plug)

	mgr := &api.Manager{Logger: slog.Default()}
	mgr.PluginProcess = pm.ProcessPluginApi

    // Start HTTP bridge with plugin adapter
	hb := httpb.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	hb.OnListen = func(a string){ bound <- a }
	if adapter, ok := hb.API.(*httpb.Adapter); ok { adapter.Plugins = pm }
	st := make(chan struct{})
	go func(){ _ = hb.ListenAndServe(st) }()
	defer close(st)
    httpAddr := <-bound

    // Wait until SendPacket returns true for the user (mapping ready)
    deadline2 := time.Now().Add(2 * time.Second)
    for {
        if xs.SendPacket("user", []byte{0x7F}) { break }
        if time.Now().After(deadline2) { t.Fatal("xmpp mapping not ready for send") }
        time.Sleep(10 * time.Millisecond)
    }

    // Call ears set API
	conn, err := net.Dial("tcp", httpAddr)
	if err != nil { t.Fatal(err) }
	defer conn.Close()
    frm := frameGET2("/ojn_api/plugin/ears/set?bunny=user&left=3&right=7")
	if _, err := conn.Write(frm); err != nil { t.Fatal(err) }
    // Read HTTP response and assert plugin returned <ok/>, indicating SendPacket succeeded
    hbuf := make([]byte, 4096)
    _ = conn.SetReadDeadline(time.Now().Add(1 * time.Second))
    n, err := conn.Read(hbuf)
    if err != nil { t.Fatal(err) }
    if !bytes.Contains(hbuf[:n], []byte("<ok/>")) {
        t.Fatalf("ears set did not return ok: %s", string(hbuf[:n]))
    }
}
