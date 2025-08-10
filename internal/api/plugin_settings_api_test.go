package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"os"
	"testing"

	api "OpenJabNab/internal/api"
	"OpenJabNab/internal/server/httpbridge"
)

func frameP(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestPluginSettingsAPI(t *testing.T) {
	dir := t.TempDir()
	mgr := &api.Manager{Logger: slog.Default(), PluginsDir: dir}
	s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	s.OnListen = func(a string){ bound <- a }
	stop := make(chan struct{})
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
	addr := <-bound

	// setsetting
	conn, _ := net.Dial("tcp", addr)
	defer conn.Close()
	_, _ = conn.Write(frameP("/ojn_api/plugin/demo/setsetting?key=foo&value=bar"))
	buf := make([]byte, 1024)
	n, _ := conn.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected: %s", string(buf[:n])) }

	// getsetting
	conn2, _ := net.Dial("tcp", addr)
	defer conn2.Close()
	_, _ = conn2.Write(frameP("/ojn_api/plugin/demo/getsetting?key=foo"))
	n, _ = conn2.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<value>bar</value>")) { t.Fatalf("unexpected: %s", string(buf[:n])) }

	// file should exist
	if _, err := os.Stat(dir + "/plugin_demo.ini"); err != nil { t.Fatal(err) }
}
