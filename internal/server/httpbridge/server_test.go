package httpbridge

import (
    "bytes"
    apiPkg "OpenJabNab/internal/api"
    "encoding/binary"
    "net"
    "testing"
    "time"

    "log/slog"
)

type testAPI struct{}

func (t testAPI) Process(rawURI string, req *Request) (string, []byte) {
	if req.URI == "/ojn_api/global/about" {
		return "text/xml", []byte(`<?xml version="1.0" encoding="UTF-8"?><api><value>ok</value></api>`)
	}
	return "text/plain", []byte("404")
}

func frame(method byte, headers, uri, body string) []byte {
	payload := []byte{method}
	payload = append(payload, []byte(headers)...)
	payload = append(payload, 0)
    payload = append(payload, []byte(uri)...)
    if method != 1 { // only POST/POSTRAW have a second NUL
        payload = append(payload, 0)
    }
	payload = append(payload, []byte(body)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestHTTPBridgeAbout(t *testing.T) {
	stop := make(chan struct{})
	var addr string
	s := &Server{Addr: "127.0.0.1:0", Logger: slog.Default(), API: testAPI{}, OnListen: func(a string){addr=a}}
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
	deadline := time.Now().Add(2*time.Second)
	for addr == "" && time.Now().Before(deadline) { time.Sleep(10*time.Millisecond) }
	if addr == "" { t.Fatal("server did not start in time") }

	conn, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	defer conn.Close()
	frm := frame(1, "Host: test\r\n", "/ojn_api/global/about", "")
	if _, err := conn.Write(frm); err != nil { t.Fatal(err) }
	buf := make([]byte, 1024)
	n, err := conn.Read(buf)
	if err != nil { t.Fatal(err) }
	if !bytes.Contains(buf[:n], []byte("<value>ok</value>")) {
		t.Fatalf("unexpected response: %s", string(buf[:n]))
	}
}

type fakeStats struct{ t, c, z, pt, pe int }
func (f fakeStats) BunnyTotals() (int, int) { return f.t, f.c }
func (f fakeStats) ZtampTotal() int { return f.z }
func (f fakeStats) PluginTotals() (int, int) { return f.pt, f.pe }

func startWithAPI(t *testing.T, api *apiPkg.Manager) (addr string, stop func()) {
    t.Helper()
    stopCh := make(chan struct{})
    srv := New("127.0.0.1:0", slog.Default(), api)
    srv.OnListen = func(a string){ addr = a }
    go func(){ _ = srv.ListenAndServe(stopCh) }()
    deadline := time.Now().Add(2*time.Second)
    for addr == "" && time.Now().Before(deadline) { time.Sleep(10*time.Millisecond) }
    if addr == "" { t.Fatal("server did not start in time") }
    return addr, func(){ close(stopCh) }
}

func TestHTTPBridgePing(t *testing.T) {
    mgr := &apiPkg.Manager{Logger: slog.Default(), Stats: fakeStats{t:64, c:10}}
    addr, stop := startWithAPI(t, mgr)
    defer stop()
    conn, err := net.Dial("tcp", addr)
    if err != nil { t.Fatal(err) }
    defer conn.Close()
    frm := frame(1, "Host: test\r\n", "/ojn_api/global/ping", "")
    if _, err := conn.Write(frm); err != nil { t.Fatal(err) }
    buf := make([]byte, 1024)
    n, err := conn.Read(buf)
    if err != nil { t.Fatal(err) }
    if !bytes.Contains(buf[:n], []byte("<value>10/64/64</value>")) {
        t.Fatalf("unexpected response: %s", string(buf[:n]))
    }
}

func TestHTTPBridgeStats(t *testing.T) {
    mgr := &apiPkg.Manager{Logger: slog.Default(), Stats: fakeStats{t:64, c:10, z:5, pt:7, pe:3}}
    addr, stop := startWithAPI(t, mgr)
    defer stop()
    conn, err := net.Dial("tcp", addr)
    if err != nil { t.Fatal(err) }
    defer conn.Close()
    frm := frame(1, "Host: test\r\n", "/ojn_api/global/stats", "")
    if _, err := conn.Write(frm); err != nil { t.Fatal(err) }
    buf := make([]byte, 2048)
    n, err := conn.Read(buf)
    if err != nil { t.Fatal(err) }
    s := string(buf[:n])
    for _, want := range []string{"<bunnies>64</bunnies>", "<connected_bunnies>10</connected_bunnies>", "<ztamps>5</ztamps>", "<plugins>7</plugins>", "<enabled_plugins>3</enabled_plugins>"} {
        if !bytes.Contains([]byte(s), []byte(want)) {
            t.Fatalf("missing %q in response: %s", want, s)
        }
    }
}
