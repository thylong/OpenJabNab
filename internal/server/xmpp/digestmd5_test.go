package xmpp

import (
	"bufio"
	"crypto/md5"
	"encoding/base64"
	"encoding/hex"
	"fmt"
	"net"
	"strings"
	"testing"
	"time"

	"log/slog"
)

func TestDigestMD5Flow(t *testing.T) {
	user := "test"
	pass := "secret"
	domain := "example.com"
	nonce := "deadbeefcafebabe"

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
	// Send DIGEST-MD5 auth request
	_, _ = bw.WriteString("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>")
	_ = bw.Flush()
	// Read challenge
    n, err := br.Read(buf)
	if err != nil { t.Fatal(err) }
    resp := string(buf[:n])
    // Extract base64 between challenge tags and decode
    start := strings.Index(resp, ">")
    end := strings.LastIndex(resp, "</challenge>")
    if start == -1 || end == -1 || end <= start+1 { t.Fatalf("invalid challenge: %s", resp) }
    chB64 := resp[start+1:end]
    decoded, err := base64.StdEncoding.DecodeString(chB64)
    if err != nil { t.Fatalf("failed to decode challenge: %v", err) }
    if !strings.Contains(string(decoded), nonce) { t.Fatalf("expected nonce in decoded challenge, got: %s", string(decoded)) }
	// Build response per RFC: username, realm, nonce, cnonce, nc, qop, digest-uri, response
	cnonce := "01020304"
	nc := "00000001"
	qop := "auth"
	digestURI := "xmpp/"+domain
	ha1 := md5Hex(fmt.Sprintf("%s:%s:%s", user, domain, pass))
	ha1ses := md5Hex(fmt.Sprintf("%s:%s:%s", ha1, nonce, cnonce))
	ha2 := md5Hex("AUTHENTICATE:" + digestURI)
	expected := md5Hex(fmt.Sprintf("%s:%s:%s:%s:%s:%s", ha1ses, nonce, nc, cnonce, qop, ha2))
	directive := fmt.Sprintf("username=\"%s\",realm=\"%s\",nonce=\"%s\",cnonce=\"%s\",nc=%s,qop=%s,digest-uri=\"%s\",response=%s,charset=utf-8", user, domain, nonce, cnonce, nc, qop, digestURI, expected)
	b64 := base64.StdEncoding.EncodeToString([]byte(directive))
	_, _ = bw.WriteString("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>"+b64+"</response>")
	_ = bw.Flush()
	// Expect success
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	n, err = br.Read(buf)
	if err != nil { t.Fatal(err) }
	if !strings.Contains(string(buf[:n]), "<success") {
		t.Fatalf("expected success, got: %s", string(buf[:n]))
	}
}

func md5Hex(s string) string { sum := md5.Sum([]byte(s)); return hex.EncodeToString(sum[:]) }
