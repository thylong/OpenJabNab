package tts_test

import (
	"encoding/binary"
	"log/slog"
	"net"
	"testing"
	"time"

	api "OpenJabNab/internal/api"
	cfgpkg "OpenJabNab/internal/config"
	plugman "OpenJabNab/internal/plugin"
	plugtts "OpenJabNab/internal/plugins/tts"
	httpb "OpenJabNab/internal/server/httpbridge"
)

func mkFrame(uri string) []byte {
	p := []byte{1}
	p = append(p, []byte("Host: test\r\n")...)
	p = append(p, 0)
	p = append(p, []byte(uri)...)
	b := make([]byte, 4)
	binary.LittleEndian.PutUint32(b, uint32(len(p)+4))
	return append(b, p...)
}

func TestTTS_ProviderSwitch_MockToMock(t *testing.T) {
	dir := t.TempDir()
	cfg := &cfgpkg.Config{RealHttpRoot: dir, TTS: "mock", TTSWorkers: 1}
	pm := plugman.NewManager()
	pl := plugtts.New(cfg)
	pm.Register(pl)
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
	// Speak once
	c, _ := net.Dial("tcp", addr); defer c.Close()
	_, _ = c.Write(mkFrame("/ojn_api/plugin/tts/speak?bunny=b1&text=A"))
	_ = c.SetReadDeadline(time.Now().Add(1*time.Second))
	_, _ = c.Read(make([]byte, 1024))
	// Simulate provider switch by creating a new plugin instance (runtime config reload would recreate plugin)
	_ = c.Close()
	pl2 := plugtts.New(&cfgpkg.Config{RealHttpRoot: dir, TTS: "mock", TTSWorkers: 1})
	pm.Register(pl2)
	// Speak again; should hit existing cache path and return quickly
	c2, _ := net.Dial("tcp", addr); defer c2.Close()
	_, _ = c2.Write(mkFrame("/ojn_api/plugin/tts/speak?bunny=b1&text=A"))
	_ = c2.SetReadDeadline(time.Now().Add(1*time.Second))
	_, _ = c2.Read(make([]byte, 1024))
}
