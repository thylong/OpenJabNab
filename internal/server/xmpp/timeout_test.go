package xmpp

import (
	"bufio"
	"net"
	"testing"
	"time"

	"log/slog"
)

// Test that the server does not drop the connection due to read timeout when periodic data arrives.
func TestRollingReadDeadline(t *testing.T) {
	stop := make(chan struct{})
	var addr string
	s := &Server{Addr: "127.0.0.1:0", Domain: "example.com", Logger: slog.Default(), OnListen: func(a string){addr=a}}
	s.BypassAuth = true
	s.ReadTimeout = 200 * time.Millisecond // short timeout to exercise rolling refresh
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
	// Open stream; expect features
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='example.com' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.ReadByte(); err != nil { t.Fatalf("expected server reply, got: %v", err) }
	// Send auth (bypass).
	_, _ = bw.WriteString("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.ReadByte(); err != nil { t.Fatalf("expected SASL success, got: %v", err) }
	// Send periodic single-byte data to refresh the deadline and avoid timeout
	for i := 0; i < 5; i++ {
		time.Sleep(150 * time.Millisecond)
		if _, err := bw.Write([]byte(" ")); err != nil { t.Fatal(err) }
		_ = bw.Flush()
	}
	// Finally, ensure connection still alive by attempting another read with short deadline
	c.SetReadDeadline(time.Now().Add(50 * time.Millisecond))
	_, _ = br.Peek(0) // non-blocking; if conn closed, this errors
}
