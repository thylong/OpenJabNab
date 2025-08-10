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
    plugears "OpenJabNab/internal/plugins/ears"
    plugradio "OpenJabNab/internal/plugins/webradio"
    plugrecord "OpenJabNab/internal/plugins/record"
    "OpenJabNab/internal/server/httpbridge"
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

func TestEarsPlugin_Get(t *testing.T) {
    pm := plugman.NewManager()
    pm.Register(plugears.New())

    mgr := &api.Manager{Logger: slog.Default()}
    mgr.PluginNames = pm.Names
    mgr.EnabledPluginNames = pm.EnabledNames
    mgr.SetPluginEnabled = pm.Enable
    mgr.PluginProcess = pm.ProcessPluginApi

    s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
    bound := make(chan string, 1)
    s.OnListen = func(a string){ bound <- a }
    if adapter, ok := s.API.(*httpbridge.Adapter); ok { adapter.Plugins = pm }
    stop := make(chan struct{})
    go func(){ _ = s.ListenAndServe(stop) }()
    defer close(stop)
    addr := <-bound

    // simulate ears event
    pm.OnEars("b1", 3, 7)

    c, _ := net.Dial("tcp", addr)
    defer c.Close()
    _, _ = c.Write(frameGET("/ojn_api/plugin/ears/get?bunny=b1"))
    buf := make([]byte, 2048)
    n, _ := c.Read(buf)
    got := string(buf[:n])
    for _, want := range []string{"<left>3</left>", "<right>7</right>"} {
        if !bytes.Contains([]byte(got), []byte(want)) {
            t.Fatalf("missing %q in response: %s", want, got)
        }
    }
}

func TestWebradioPlugin_Flows(t *testing.T) {
    dir := t.TempDir()
    cfg := &cfgpkg.Config{PluginsDir: dir}
    pm := plugman.NewManager()
    pm.Register(plugradio.New(cfg))

    mgr := &api.Manager{Logger: slog.Default(), PluginsDir: dir}
    mgr.PluginProcess = pm.ProcessPluginApi

    s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
    bound := make(chan string, 1)
    s.OnListen = func(a string){ bound <- a }
    if adapter, ok := s.API.(*httpbridge.Adapter); ok { adapter.Plugins = pm }
    stop := make(chan struct{})
    go func(){ _ = s.ListenAndServe(stop) }()
    defer close(stop)
    addr := <-bound
    buf := make([]byte, 4096)

    // list (empty)
    c1, _ := net.Dial("tcp", addr); defer c1.Close()
    _, _ = c1.Write(frameGET("/ojn_api/plugin/webradio/list"))
    n, _ := c1.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<list>")) { t.Fatalf("unexpected list: %s", string(buf[:n])) }

    // add station
    c2, _ := net.Dial("tcp", addr); defer c2.Close()
    _, _ = c2.Write(frameGET("/ojn_api/plugin/webradio/add?name=rock&url=http://example/stream"))
    n, _ = c2.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected add: %s", string(buf[:n])) }

    // list should contain station
    c3, _ := net.Dial("tcp", addr); defer c3.Close()
    _, _ = c3.Write(frameGET("/ojn_api/plugin/webradio/list"))
    n, _ = c3.Read(buf)
    if !bytes.Contains(buf[:n], []byte("name=\"rock\"")) { t.Fatalf("station missing: %s", string(buf[:n])) }

    // set station for bunny
    c4, _ := net.Dial("tcp", addr); defer c4.Close()
    _, _ = c4.Write(frameGET("/ojn_api/plugin/webradio/set?bunny=b1&name=rock"))
    n, _ = c4.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected set: %s", string(buf[:n])) }

    // status (not playing)
    c5, _ := net.Dial("tcp", addr); defer c5.Close()
    _, _ = c5.Write(frameGET("/ojn_api/plugin/webradio/status?bunny=b1"))
    n, _ = c5.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<playing>false</playing>")) || !bytes.Contains(buf[:n], []byte("<station>rock</station>")) {
        t.Fatalf("unexpected status: %s", string(buf[:n]))
    }

    // play
    c6, _ := net.Dial("tcp", addr); defer c6.Close()
    _, _ = c6.Write(frameGET("/ojn_api/plugin/webradio/play?bunny=b1"))
    n, _ = c6.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected play: %s", string(buf[:n])) }

    // status (playing)
    c7, _ := net.Dial("tcp", addr); defer c7.Close()
    _, _ = c7.Write(frameGET("/ojn_api/plugin/webradio/status?bunny=b1"))
    n, _ = c7.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<playing>true</playing>")) { t.Fatalf("unexpected status after play: %s", string(buf[:n])) }

    // stop
    c8, _ := net.Dial("tcp", addr); defer c8.Close()
    _, _ = c8.Write(frameGET("/ojn_api/plugin/webradio/stop?bunny=b1"))
    n, _ = c8.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected stop: %s", string(buf[:n])) }

    // del station
    c9, _ := net.Dial("tcp", addr); defer c9.Close()
    _, _ = c9.Write(frameGET("/ojn_api/plugin/webradio/del?name=rock"))
    n, _ = c9.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected del: %s", string(buf[:n])) }
}

func TestRecordPlugin_Flows(t *testing.T) {
    dir := t.TempDir()
    cfg := &cfgpkg.Config{PluginsDir: dir}
    pm := plugman.NewManager()
    pm.Register(plugrecord.New(cfg))

    mgr := &api.Manager{Logger: slog.Default(), PluginsDir: dir}
    mgr.PluginProcess = pm.ProcessPluginApi

    s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
    bound := make(chan string, 1)
    s.OnListen = func(a string){ bound <- a }
    if adapter, ok := s.API.(*httpbridge.Adapter); ok { adapter.Plugins = pm }
    stop := make(chan struct{})
    go func(){ _ = s.ListenAndServe(stop) }()
    defer close(stop)
    addr := <-bound
    buf := make([]byte, 4096)

    // status initial
    c1, _ := net.Dial("tcp", addr); defer c1.Close()
    _, _ = c1.Write(frameGET("/ojn_api/plugin/record/status?bunny=b2"))
    n, _ := c1.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<recording>false</recording>")) { t.Fatalf("unexpected initial status: %s", string(buf[:n])) }

    // start
    c2, _ := net.Dial("tcp", addr); defer c2.Close()
    _, _ = c2.Write(frameGET("/ojn_api/plugin/record/start?bunny=b2&seconds=5"))
    n, _ = c2.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected start: %s", string(buf[:n])) }

    // status recording
    c3, _ := net.Dial("tcp", addr); defer c3.Close()
    _, _ = c3.Write(frameGET("/ojn_api/plugin/record/status?bunny=b2"))
    n, _ = c3.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<recording>true</recording>")) || !bytes.Contains(buf[:n], []byte("<seconds>5</seconds>")) {
        t.Fatalf("unexpected status after start: %s", string(buf[:n]))
    }

    // stop
    c4, _ := net.Dial("tcp", addr); defer c4.Close()
    _, _ = c4.Write(frameGET("/ojn_api/plugin/record/stop?bunny=b2"))
    n, _ = c4.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected stop: %s", string(buf[:n])) }

    // status stopped
    c5, _ := net.Dial("tcp", addr); defer c5.Close()
    _, _ = c5.Write(frameGET("/ojn_api/plugin/record/status?bunny=b2"))
    n, _ = c5.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<recording>false</recording>")) { t.Fatalf("unexpected status after stop: %s", string(buf[:n])) }
}
