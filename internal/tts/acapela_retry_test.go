package tts

import (
	"context"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"time"
)

func TestAcapela_RetryTransient(t *testing.T) {
	tries := 0
	audio := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request){ w.Header().Set("Content-Type","audio/mpeg"); w.Write([]byte{0}) }))
	t.Cleanup(audio.Close)
	api := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request){
		if r.URL.Path == "/synthesize" {
			tries++
			if tries == 1 { w.WriteHeader(500); w.Write([]byte("err")); return }
			w.Header().Set("Content-Type","application/json")
			w.Write([]byte(`{"snd_url":"`+audio.URL+`/x.mp3"}`))
			return
		}
		w.WriteHeader(404)
	}))
	t.Cleanup(api.Close)
	_ = os.Setenv("ACAPELA_BASE_URL", api.URL)
	_ = os.Setenv("ACAPELA_LOGIN", "u")
	_ = os.Setenv("ACAPELA_PASSWORD", "p")
	_ = os.Setenv("ACAPELA_APPLICATION", "app")
	p := &acapelaProvider{httpClient: api.Client()}
	ctx, cancel := context.WithTimeout(context.Background(), 2*time.Second)
	defer cancel()
	data, _, err := p.Synthesize(ctx, "hello", "enu_william")
	if err != nil { t.Fatal(err) }
	if len(data) == 0 || tries < 2 { t.Fatalf("expected retry, tries=%d", tries) }
}
