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

func setupAuthConn(t *testing.T, domain, user, pass, nonce string) (*bufio.Reader, *bufio.Writer, net.Conn, func()) {
	stop := make(chan struct{})
	var addr string
	s := &Server{Addr: "127.0.0.1:0", Domain: domain, Logger: slog.Default(), OnListen: func(a string){addr=a}}
	s.GetPassword = func(u string) (string, bool) { if u==user { return pass, true }; return "", false }
	s.NonceFactory = func() string { return nonce }
	go func(){ _ = s.ListenAndServe(stop) }()
	cleanup := func(){ close(stop) }
	deadline := time.Now().Add(2*time.Second)
	for addr == "" && time.Now().Before(deadline) { time.Sleep(10*time.Millisecond) }
	if addr == "" { t.Fatal("server did not start in time") }

	c, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	bw := bufio.NewWriter(c)
	br := bufio.NewReader(c)
	// Stream open
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='"+domain+"' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	buf := make([]byte, 4096)
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// DIGEST-MD5 auth request
	_, _ = bw.WriteString("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>")
	_ = bw.Flush()
	// Read challenge
	n, err := br.Read(buf)
	if err != nil { t.Fatal(err) }
	resp := string(buf[:n])
	start := strings.Index(resp, ">")
	end := strings.LastIndex(resp, "</challenge>")
	decoded, _ := base64.StdEncoding.DecodeString(resp[start+1 : end])
	if !strings.Contains(string(decoded), nonce) { t.Fatalf("bad challenge: %s", string(decoded)) }
	// build response quickly (reuse from previous test)
	cnonce := "01020304"; nc := "00000001"; digestURI := "xmpp/"+domain
	expected := md5Hex(md5Hex(md5Hex(user+":"+domain+":"+pass)+":"+nonce+":"+cnonce) + ":" + nonce + ":" + nc + ":" + cnonce + ":auth:" + md5Hex("AUTHENTICATE:"+digestURI))
	directive := "username=\""+user+"\",realm=\""+domain+"\",nonce=\""+nonce+"\",cnonce=\""+cnonce+"\",nc="+nc+",qop=auth,digest-uri=\""+digestURI+"\",response="+expected+",charset=utf-8"
	_, _ = bw.WriteString("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>" + base64.StdEncoding.EncodeToString([]byte(directive)) + "</response>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// second stream
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='"+domain+"' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// bind resource
	_, _ = bw.WriteString("<iq type='set' id='1' to='"+user+"@"+domain+"'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>streaming</resource></bind></iq>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	return br, bw, c, cleanup
}

func TestMessageButtonAndEars(t *testing.T) {
	br, bw, c, cleanup := setupAuthConn(t, "example.com", "user", "pass", "abc123")
	defer cleanup(); defer c.Close()
	// Send button single click
	_, _ = bw.WriteString("<message><button xmlns=\"violet:nabaztag:button\"><clic>2</clic></button></message>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(200*time.Millisecond))
	// No server response is expected for button; test doesn't read
	// Send ears position
	_, _ = bw.WriteString("<message><ears xmlns=\"violet:nabaztag:ears\"><left>5</left><right>7</right></ears></message>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(200*time.Millisecond))
	// No server response expected; ensure connection still alive by sending presence echo
	_, _ = bw.WriteString("<presence from='user@example.com/streaming' id='p1'></presence>")
	_ = bw.Flush()
	buf := make([]byte, 1024)
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	n, err := br.Read(buf)
	if err != nil { t.Fatal(err) }
	if !strings.Contains(string(buf[:n]), "<presence from='user@example.com/streaming' to='user@example.com/streaming' id='p1'/>") {
		t.Fatalf("presence echo failed: %s", string(buf[:n]))
	}
}
