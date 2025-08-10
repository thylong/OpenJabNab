package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"testing"

	api "OpenJabNab/internal/api"
	"OpenJabNab/internal/server/httpbridge"
)

func framePS(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestPluginSettingsListAndDelete(t *testing.T) {
	mgr := &api.Manager{Logger: slog.Default(), PluginsDir: t.TempDir()}
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
	_, _ = conn.Write(framePS("/ojn_api/plugin/demo/setsetting?key=foo&value=bar"))
	buf := make([]byte, 1024)
	n, _ := conn.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected: %s", string(buf[:n])) }

	// listsettings
	c2, _ := net.Dial("tcp", addr)
	defer c2.Close()
	_, _ = c2.Write(framePS("/ojn_api/plugin/demo/listsettings"))
	n, _ = c2.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<item>foo</item>")) { t.Fatalf("unexpected: %s", string(buf[:n])) }

	// delsetting
	c3, _ := net.Dial("tcp", addr)
	defer c3.Close()
	_, _ = c3.Write(framePS("/ojn_api/plugin/demo/delsetting?key=foo"))
	n, _ = c3.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected: %s", string(buf[:n])) }
}
