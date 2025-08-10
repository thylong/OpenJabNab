package tts_test

import (
	"bytes"
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

func frameGET(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestTTS_CacheReuse_SameURL(t *testing.T) {
	dir := t.TempDir()
	cfg := &cfgpkg.Config{RealHttpRoot: dir, TTS: "mock"}
	pm := plugman.NewManager()
    cfg.TTSWorkers = 1
    pl := plugtts.New(cfg)
	// intercept MU messages to capture URL
	var lastURL string
	pl.SetPacketSender(func(id string, p []byte) bool {
		// p is raw message "MU <url>\nMW\n"
		if i := bytes.Index(p, []byte("MU ")); i >= 0 {
			j := bytes.Index(p[i+3:], []byte("\n"))
			if j > 0 { lastURL = string(p[i+3 : i+3+j]) }
		}
		return true
	})
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

    // first speak (allow time for worker to process)
	conn, _ := net.Dial("tcp", addr); defer conn.Close()
	_, _ = conn.Write(frameGET("/ojn_api/plugin/tts/speak?bunny=b1&text=hello"))
	b := make([]byte, 4096)
	_ = conn.SetReadDeadline(time.Now().Add(1*time.Second))
	_, _ = conn.Read(b)
    time.Sleep(300 * time.Millisecond)
	first := lastURL
	if first == "" { t.Fatal("missing first url") }
	// second speak, expect same url (cache hit)
	c2, _ := net.Dial("tcp", addr); defer c2.Close()
	_, _ = c2.Write(frameGET("/ojn_api/plugin/tts/speak?bunny=b1&text=hello"))
    _ = c2.SetReadDeadline(time.Now().Add(1*time.Second))
	_, _ = c2.Read(b)
    time.Sleep(100 * time.Millisecond)
	second := lastURL
	if second != first { t.Fatalf("expected same url, got %q vs %q", second, first) }
}
