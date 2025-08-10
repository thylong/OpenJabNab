package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"testing"

	api "OpenJabNab/internal/api"
	plugman "OpenJabNab/internal/plugin"
	pluglog "OpenJabNab/internal/plugins/logger"
	"OpenJabNab/internal/server/httpbridge"
)

func framePED(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestPluginEnableDisable(t *testing.T) {
	pm := plugman.NewManager()
	pm.Register(pluglog.New(slog.Default()))
	mgr := &api.Manager{Logger: slog.Default()}
	mgr.PluginNames = pm.Names
	mgr.EnabledPluginNames = pm.EnabledNames
	mgr.SetPluginEnabled = pm.Enable
	s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	s.OnListen = func(a string){ bound <- a }
	stop := make(chan struct{})
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
	addr := <-bound

	// list enabled
	conn, _ := net.Dial("tcp", addr)
	defer conn.Close()
	_, _ = conn.Write(framePED("/ojn_api/plugins-enabled"))
	buf := make([]byte, 1024)
	n, _ := conn.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<item>logger</item>")) { t.Fatalf("unexpected enabled list: %s", string(buf[:n])) }

	// disable
	c2, _ := net.Dial("tcp", addr)
	defer c2.Close()
	_, _ = c2.Write(framePED("/ojn_api/plugin-disable/logger"))
	n, _ = c2.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected disable: %s", string(buf[:n])) }

	// enabled list should be empty
	c3, _ := net.Dial("tcp", addr)
	defer c3.Close()
	_, _ = c3.Write(framePED("/ojn_api/plugins-enabled"))
	n, _ = c3.Read(buf)
	if bytes.Contains(buf[:n], []byte("<item>logger</item>")) { t.Fatalf("expected logger disabled, got: %s", string(buf[:n])) }

	// enable
	c4, _ := net.Dial("tcp", addr)
	defer c4.Close()
	_, _ = c4.Write(framePED("/ojn_api/plugin-enable/logger"))
	n, _ = c4.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected enable: %s", string(buf[:n])) }
}
