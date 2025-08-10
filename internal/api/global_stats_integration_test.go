package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"testing"

	api "OpenJabNab/internal/api"
	plugman "OpenJabNab/internal/plugin"
	plugloc "OpenJabNab/internal/plugins/locate"
	plugstats "OpenJabNab/internal/plugins/stats"
	"OpenJabNab/internal/server/httpbridge"
	cfgpkg "OpenJabNab/internal/config"
	stats "OpenJabNab/internal/stats"
)

func frameGETgs(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestGlobalStats_PluginTotalsReflectManager(t *testing.T) {
	cfg := &cfgpkg.Config{}
	pm := plugman.NewManager()
	pm.Register(plugloc.New(cfg))
	pm.Register(plugstats.New(stats.Null{})) // counting not needed here; live provider uses manager

	live := stats.NewLive(cfg, nil, nil)
	// inject plugin manager into live stats to provide totals
	if s, ok := interface{}(live).(*stats.Live); ok {
		s.SetPluginManager(pm)
	}
	mgr := &api.Manager{Logger: slog.Default(), Stats: live}
	mgr.PluginNames = pm.Names
	mgr.EnabledPluginNames = pm.EnabledNames

	hb := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	hb.OnListen = func(a string){ bound <- a }
	if adapter, ok := hb.API.(*httpbridge.Adapter); ok { adapter.Plugins = pm }
	stop := make(chan struct{})
	go func(){ _ = hb.ListenAndServe(stop) }()
	defer close(stop)
	addr := <-bound

	c, _ := net.Dial("tcp", addr); defer c.Close()
	_, _ = c.Write(frameGETgs("/ojn_api/global/stats"))
	buf := make([]byte, 4096)
	n, _ := c.Read(buf)
	got := string(buf[:n])
	if !bytes.Contains([]byte(got), []byte("<plugins>")) || !bytes.Contains([]byte(got), []byte("<enabled_plugins>")) {
		t.Fatalf("missing plugin totals in stats: %s", got)
	}
}
