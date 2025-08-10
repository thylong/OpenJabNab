package tts

import (
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"context"
)

func TestAcapela_Synthesize_JSON_URL(t *testing.T) {
	// fake audio server
	audio := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request){ w.Header().Set("Content-Type", "audio/mpeg"); _, _ = w.Write([]byte{0x49,0x44,0x33}) }))
	t.Cleanup(audio.Close)
	// fake API server
	api := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request){
		if r.URL.Path == "/synthesize" {
			w.Header().Set("Content-Type", "application/json")
			_, _ = w.Write([]byte(`{"snd_url":"`+audio.URL+`/file.mp3"}`))
			return
		}
		if r.URL.Path == "/voices" {
			w.Header().Set("Content-Type", "application/json")
			_, _ = w.Write([]byte(`{"voices":[{"name":"enu_william","language":"en-US","gender":"M"}]}`))
			return
		}
		w.WriteHeader(404)
	}))
	t.Cleanup(api.Close)
	// env
	_ = os.Setenv("ACAPELA_BASE_URL", api.URL)
	_ = os.Setenv("ACAPELA_LOGIN", "u")
	_ = os.Setenv("ACAPELA_PASSWORD", "p")
	_ = os.Setenv("ACAPELA_APPLICATION", "app")
	p := &acapelaProvider{httpClient: api.Client()}
    data, codec, err := p.Synthesize(context.Background(), "hello", SynthesisOptions{VoiceID: "enu_william", Codec: "mp3"})
	if err != nil { t.Fatal(err) }
	if codec != "mp3" { t.Fatalf("expected mp3, got %s", codec) }
	if len(data) == 0 { t.Fatal("no data") }
	vs, err := p.ListVoices(context.Background())
	if err != nil || len(vs) == 0 { t.Fatalf("voices failed: %v %v", vs, err) }
}

func TestAcapela_InvalidCreds_Error(t *testing.T) {
	api := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request){ w.WriteHeader(401); _, _ = w.Write([]byte("no")) }))
	t.Cleanup(api.Close)
	_ = os.Setenv("ACAPELA_BASE_URL", api.URL)
	_ = os.Setenv("ACAPELA_LOGIN", "u")
	_ = os.Setenv("ACAPELA_PASSWORD", "p")
	_ = os.Setenv("ACAPELA_APPLICATION", "app")
	p := &acapelaProvider{httpClient: api.Client()}
    if _, _, err := p.Synthesize(context.Background(), "hello", SynthesisOptions{VoiceID: "enu_william", Codec: "mp3"}); err == nil { t.Fatal("expected error") }
}
