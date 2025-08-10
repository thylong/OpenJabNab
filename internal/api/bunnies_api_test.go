package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"testing"

	api "OpenJabNab/internal/api"
	"OpenJabNab/internal/bunny"
	"OpenJabNab/internal/server/httpbridge"
)

func frameGet(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestBunnySetNameAndGet(t *testing.T) {
	mgr := &api.Manager{Logger: slog.Default(), Stats: fakeProv{}}
	bun := bunny.NewManager(64)
	bun.Connect("b1")
	mgr.Bunnies = api.DefaultBunnyAPI{B: bun}

	s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	s.OnListen = func(a string){ bound <- a }
	stop := make(chan struct{})
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
	addr := <-bound

	conn, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	defer conn.Close()

    // setname
    if _, err := conn.Write(frameGet("/ojn_api/bunny/b1/setname?name=Fluffy")); err != nil { t.Fatal(err) }
    buf := make([]byte, 1024)
    n, err := conn.Read(buf)
    if err != nil { t.Fatal(err) }
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected response: %s", string(buf[:n])) }

    // name (new connection per request, mirroring wrapper behavior)
    conn2, err := net.Dial("tcp", addr)
    if err != nil { t.Fatal(err) }
    defer conn2.Close()
    if _, err := conn2.Write(frameGet("/ojn_api/bunny/b1/name")); err != nil { t.Fatal(err) }
    n, err = conn2.Read(buf)
    if err != nil { t.Fatal(err) }
    if !bytes.Contains(buf[:n], []byte("<name>Fluffy</name>")) { t.Fatalf("unexpected response: %s", string(buf[:n])) }
}
