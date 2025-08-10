package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"testing"

	api "OpenJabNab/internal/api"
	cfgpkg "OpenJabNab/internal/config"
	plugman "OpenJabNab/internal/plugin"
	plugradio "OpenJabNab/internal/plugins/webradio"
	httpb "OpenJabNab/internal/server/httpbridge"
)

func frameGETwr(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestWebradio_RFID_AddRemove_OnRFID_Play(t *testing.T) {
	dir := t.TempDir()
	cfg := &cfgpkg.Config{PluginsDir: dir}
	pm := plugman.NewManager()
	// create plugin instance to inject sender
	wr := plugradio.New(cfg)
	sent := 0
	var sentID string
	wr.SetPacketSender(func(id string, payload []byte) bool { sent++; sentID = id; return true })
	pm.Register(wr)

	mgr := &api.Manager{Logger: slog.Default(), PluginsDir: dir}
	mgr.PluginProcess = pm.ProcessPluginApi

	hb := httpb.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	hb.OnListen = func(a string){ bound <- a }
	if adapter, ok := hb.API.(*httpb.Adapter); ok { adapter.Plugins = pm }
	stop := make(chan struct{})
	go func(){ _ = hb.ListenAndServe(stop) }()
	defer close(stop)
	addr := <-bound

	buf := make([]byte, 4096)
	// add station
	c1, _ := net.Dial("tcp", addr); defer c1.Close()
	_, _ = c1.Write(frameGETwr("/ojn_api/plugin/webradio/add?name=rock&url=http://example/stream"))
	n, _ := c1.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected add: %s", string(buf[:n])) }

	// map RFID for bunny b1
	c2, _ := net.Dial("tcp", addr); defer c2.Close()
	_, _ = c2.Write(frameGETwr("/ojn_api/plugin/webradio/addrfid?bunny=b1&tag=a1b2&name=rock"))
	n, _ = c2.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected addrfid: %s", string(buf[:n])) }

	// simulate RFID event
	pm.OnRFID("b1", "A1B2")
	if sent == 0 || sentID != "b1" { t.Fatalf("expected play packet sent, got sent=%d id=%s", sent, sentID) }

	// status should indicate playing and station rock
	c3, _ := net.Dial("tcp", addr); defer c3.Close()
	_, _ = c3.Write(frameGETwr("/ojn_api/plugin/webradio/status?bunny=b1"))
	n, _ = c3.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<playing>true</playing>")) || !bytes.Contains(buf[:n], []byte("<station>rock</station>")) {
		t.Fatalf("unexpected status after RFID: %s", string(buf[:n])) }

	// remove RFID mapping
	c4, _ := net.Dial("tcp", addr); defer c4.Close()
	_, _ = c4.Write(frameGETwr("/ojn_api/plugin/webradio/removerfid?bunny=b1&tag=a1b2"))
	n, _ = c4.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected removerfid: %s", string(buf[:n])) }

	sent = 0
	pm.OnRFID("b1", "A1B2")
	if sent != 0 { t.Fatalf("expected no send after removerfid, got %d", sent) }
}
