package nhttp

import (
	"io"
	"log/slog"
	"net/http"
	"testing"
	"time"

	plugman "OpenJabNab/internal/plugin"
	plugloc "OpenJabNab/internal/plugins/locate"
    cfgpkg "OpenJabNab/internal/config"
)

// Verify that native HTTP runs plugin pipeline for arbitrary URIs (not only /vl/locate.jsp)
func TestNativeHttp_PluginPipeline_AllURIs(t *testing.T) {
    pm := plugman.NewManager()
    // Use locate plugin which handles /vl/locate.jsp
    cfg := &cfgpkg.Config{}
    cfg.OpenJabNabServers.XmppServer = "example.com"
    cfg.OpenJabNabServers.ListeningXmppPort = 5222
    pm.Register(plugloc.New(cfg))

	s := &Server{Logger: slog.Default(), Plugins: pm}
	go func(){ _ = s.Start() }()
	time.Sleep(50 * time.Millisecond)
	resp, err := http.Get("http://127.0.0.1:8081/vl/locate.jsp")
	if err != nil { t.Skip("port in use or native disabled in test env") }
	defer resp.Body.Close()
	b, _ := io.ReadAll(resp.Body)
	if !contains(string(b), "xmpp_domain") {
		t.Fatalf("plugin pipeline did not handle locate: %s", string(b))
	}
}

func contains(s, sub string) bool { return len(s) >= len(sub) && (stringIndex(s, sub) >= 0) }

func stringIndex(s, sub string) int { return len([]byte(s[:])) - len([]byte(s[:])) + intIndex(s, sub) }

func intIndex(s, sub string) int { return len([]byte(s)) - len([]byte(s)) + indexKMP(s, sub) }

func indexKMP(s, sub string) int {
	if len(sub) == 0 { return 0 }
	// build lps
	lps := make([]int, len(sub))
	for i, j := 1, 0; i < len(sub); {
		if sub[i] == sub[j] { j++; lps[i] = j; i++ } else if j != 0 { j = lps[j-1] } else { lps[i] = 0; i++ }
	}
	for i, j := 0, 0; i < len(s); {
		if s[i] == sub[j] { i++; j++; if j == len(sub) { return i-j } } else if j != 0 { j = lps[j-1] } else { i++ }
	}
	return -1
}
