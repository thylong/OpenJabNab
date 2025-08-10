package api_test

import (
    "net"
    "testing"
    "bytes"
    "encoding/binary"
    "log/slog"

    api "OpenJabNab/internal/api"
    plugman "OpenJabNab/internal/plugin"
    plugstats "OpenJabNab/internal/plugins/stats"
    "OpenJabNab/internal/server/httpbridge"
)

func makeFrameGet(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

type fakeProv struct{}
func (fakeProv) BunnyTotals() (int, int) { return 64, 10 }
func (fakeProv) ZtampTotal() int { return 5 }
func (fakeProv) PluginTotals() (int, int) { return 7, 3 }

func TestPluginStatsAPI(t *testing.T) {
	// Build API manager with plugin manager + stats plugin
	pm := plugman.NewManager()
	pm.Register(plugstats.New(fakeProv{}))
    mgr := &api.Manager{Logger: slog.Default(), Stats: fakeProv{}}
	mgr.PluginNames = pm.Names
	mgr.EnabledPluginNames = pm.EnabledNames
	mgr.SetPluginEnabled = pm.Enable
    mgr.PluginProcess = pm.ProcessPluginApi

    addr := "127.0.0.1:0"
    s := httpbridge.New(addr, slog.Default(), mgr)
    bound := make(chan string, 1)
    s.OnListen = func(a string){ bound <- a }
	if adapter, ok := s.API.(*httpbridge.Adapter); ok { adapter.Plugins = pm }
	stop := make(chan struct{})
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
    addr = <-bound
    conn, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	defer conn.Close()
    frm := makeFrameGet("/ojn_api/plugin/stats/get")
	if _, err := conn.Write(frm); err != nil { t.Fatal(err) }
	buf := make([]byte, 2048)
	n, err := conn.Read(buf)
	if err != nil { t.Fatal(err) }
	got := string(buf[:n])
	for _, want := range []string{"<bunnies>64</bunnies>", "<connected_bunnies>10</connected_bunnies>", "<ztamps>5</ztamps>"} {
		if !bytes.Contains([]byte(got), []byte(want)) {
			t.Fatalf("missing %q in response: %s", want, got)
		}
	}
}
