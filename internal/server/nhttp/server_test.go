package nhttp

import (
	"io"
	"log/slog"
	"net/http"
	"testing"
	"time"

	api "OpenJabNab/internal/api"
)

func TestNativeHttpApi(t *testing.T) {
	mgr := &api.Manager{Logger: slog.Default()}
	s := &Server{API: mgr, Logger: slog.Default()}
	go func(){ _ = s.Start() }()
	time.Sleep(50 * time.Millisecond)
	resp, err := http.Get("http://127.0.0.1:8081/ojn_api/global/about")
	if err != nil { t.Skip("port in use or native disabled in test env") }
	defer resp.Body.Close()
	b, _ := io.ReadAll(resp.Body)
	if len(b) == 0 { t.Fatalf("empty response") }
}
