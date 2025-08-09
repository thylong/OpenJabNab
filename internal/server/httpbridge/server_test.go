package httpbridge

import (
    "bytes"
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
	payload = append(payload, 0)
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
