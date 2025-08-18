package xmpp

import (
	"bufio"
	"encoding/base64"
	"net"
	"strings"
	"testing"
	"time"

	"log/slog"
)

// end-to-end: stream -> DIGEST-MD5 -> success -> second stream -> bind -> session -> sources
func TestIQBindSessionSources(t *testing.T) {
	user := "test"
	pass := "secret"
	domain := "example.com"
	nonce := "a1b2c3d4e5f6"

	stop := make(chan struct{})
	var addr string
	s := &Server{Addr: "127.0.0.1:0", Domain: domain, Logger: slog.Default(), OnListen: func(a string){addr=a}}
	s.GetPassword = func(u string) (string, bool) { if u==user { return pass, true }; return "", false }
	s.NonceFactory = func() string { return nonce }
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
	deadline := time.Now().Add(2*time.Second)
	for addr == "" && time.Now().Before(deadline) { time.Sleep(10*time.Millisecond) }
	if addr == "" { t.Fatal("server did not start in time") }

	c, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	defer c.Close()
	bw := bufio.NewWriter(c)
	br := bufio.NewReader(c)
	// Open stream
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='"+domain+"' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	buf := make([]byte, 4096)
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// Request DIGEST-MD5
	_, _ = bw.WriteString("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>")
	_ = bw.Flush()
	// Read challenge
	n, err := br.Read(buf)
	if err != nil { t.Fatal(err) }
	resp := string(buf[:n])
	start := strings.Index(resp, ">")
	end := strings.LastIndex(resp, "</challenge>")
	chB64 := resp[start+1 : end]
	decoded, _ := base64.StdEncoding.DecodeString(chB64)
	if !strings.Contains(string(decoded), nonce) { t.Fatalf("bad challenge: %s", string(decoded)) }
	// Build response
	cnonce := "01020304"
	nc := "00000001"
	digestURI := "xmpp/" + domain
	// reuse helper from other test by copying simple MD5 hex here
	md5h := func(s string) string { return md5Hex(s) }
	expected := md5h(md5h(md5h(user+":"+domain+":"+pass)+":"+nonce+":"+cnonce) + ":" + nonce + ":" + nc + ":" + cnonce + ":auth:" + md5h("AUTHENTICATE:"+digestURI))
	directive := "username=\""+user+"\",realm=\""+domain+"\",nonce=\""+nonce+"\",cnonce=\""+cnonce+"\",nc="+nc+",qop=auth,digest-uri=\""+digestURI+"\",response="+expected+",charset=utf-8"
	_, _ = bw.WriteString("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>" + base64.StdEncoding.EncodeToString([]byte(directive)) + "</response>")
	_ = bw.Flush()
	// Expect success
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// Open second stream
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='"+domain+"' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// Send bind IQ
	_, _ = bw.WriteString("<iq type='set' id='1' to='"+user+"@"+domain+"'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>streaming</resource></bind></iq>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	n, err = br.Read(buf)
	if err != nil { t.Fatal(err) }
	if !strings.Contains(string(buf[:n]), "<bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><jid>"+user+"@"+domain+"/streaming</jid></bind>") {
		t.Fatalf("bind reply missing jid: %s", string(buf[:n]))
	}
	// Session IQ
	_, _ = bw.WriteString("<iq type='set' id='2'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// Sources IQ
	_, _ = bw.WriteString("<iq type='get' id='3'><query xmlns=\"violet:iq:sources\"><packet xmlns=\"violet:packet\" format=\"1.0\"/></query></iq>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	n, err = br.Read(buf)
	if err != nil { t.Fatal(err) }
    if !strings.Contains(string(buf[:n]), "<query xmlns='violet:iq:sources'><packet xmlns='violet:packet' format='1.0' ttl='604800'>") {
		t.Fatalf("unexpected sources reply: %s", string(buf[:n]))
	}
}
