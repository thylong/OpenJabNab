package xmpp

import (
	"bufio"
	"net"
	"strings"
	"testing"
	"time"

	"log/slog"
)

func TestXMPPStreamFeatures(t *testing.T) {
	stop := make(chan struct{})
	var addr string
	s := &Server{Addr: "127.0.0.1:0", Domain: "example.com", Logger: slog.Default(), OnListen: func(a string){addr=a}}
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
	// Send initial stream header as device would
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='example.com' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	buf := make([]byte, 2048)
	n, err := br.Read(buf)
	if err != nil { t.Fatal(err) }
	resp := string(buf[:n])
	if !strings.Contains(resp, "<mechanisms") {
		t.Fatalf("expected mechanisms in response, got: %s", resp)
	}
}
