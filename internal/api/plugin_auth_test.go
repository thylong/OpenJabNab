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
    plugauth "OpenJabNab/internal/plugins/auth"
    "OpenJabNab/internal/server/httpbridge"
)

func frameGA(uri string) []byte {
    payload := []byte{1}
    payload = append(payload, []byte("Host: test\r\n")...)
    payload = append(payload, 0)
    payload = append(payload, []byte(uri)...)
    buf := make([]byte, 4)
    binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
    return append(buf, payload...)
}

func TestAuthPluginParity(t *testing.T) {
    dir := t.TempDir()
    cfg := &cfgpkg.Config{PluginsDir: dir}
    pm := plugman.NewManager()
    pm.Register(plugauth.New(cfg))

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

    // list methods
    c1, _ := net.Dial("tcp", addr); defer c1.Close()
    _, _ = c1.Write(frameGA("/ojn_api/plugin/auth/getListOfAuthMethods"))
    n, _ := c1.Read(buf)
    if !bytes.Contains(buf[:n], []byte("DIGEST-MD5")) || !bytes.Contains(buf[:n], []byte("PLAIN")) { t.Fatalf("unexpected list: %s", string(buf[:n])) }

    // set method
    c2, _ := net.Dial("tcp", addr); defer c2.Close()
    _, _ = c2.Write(frameGA("/ojn_api/plugin/auth/setAuthMethod?name=BOTH"))
    n, _ = c2.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected set: %s", string(buf[:n])) }

    // get method
    c3, _ := net.Dial("tcp", addr); defer c3.Close()
    _, _ = c3.Write(frameGA("/ojn_api/plugin/auth/getAuthMethod"))
    n, _ = c3.Read(buf)
    if !bytes.Contains(buf[:n], []byte("<method>BOTH</method>")) { t.Fatalf("unexpected get: %s", string(buf[:n])) }
}
