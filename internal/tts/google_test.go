package tts

import (
	"context"
	"encoding/base64"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
)

func TestGoogle_Voices_And_Synth(t *testing.T) {
	// Fake token server
	tok := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request){ w.Header().Set("Content-Type","application/json"); _ = json.NewEncoder(w).Encode(map[string]any{"access_token":"x","expires_in":3600}) }))
	t.Cleanup(tok.Close)
	// Fake API server
	api := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request){
		switch r.URL.Path {
		case "/v1/voices":
			_ = json.NewEncoder(w).Encode(map[string]any{"voices": []map[string]any{{"name":"en-US-Standard-A","languageCodes":[]string{"en-US"},"ssmlGender":"F"}}})
			return
		case "/v1/text:synthesize":
			_ = json.NewEncoder(w).Encode(map[string]string{"audioContent": base64.StdEncoding.EncodeToString([]byte{0x49,0x44,0x33})})
			return
		default:
			w.WriteHeader(404)
		}
	}))
	t.Cleanup(api.Close)
	_ = os.Setenv("GOOGLE_TTS_ENDPOINT", api.URL)
	_ = os.Setenv("GOOGLE_APPLICATION_CREDENTIALS", tok.URL) // not used; token mocked via endpoint? we set direct token env instead
	_ = os.Setenv("GOOGLE_TTS_TOKEN", "x")
	p := &googleProvider{httpClient: api.Client(), baseURL: api.URL}
	vs, err := p.ListVoices(context.Background())
	if err != nil || len(vs) == 0 { t.Fatalf("voices failed: %v %v", vs, err) }
    data, codec, err := p.Synthesize(context.Background(), "hello", SynthesisOptions{VoiceID: "en-US-Standard-A", Codec: "mp3"})
	if err != nil { t.Fatal(err) }
	if codec != "mp3" || len(data) == 0 { t.Fatalf("bad synth: codec=%s len=%d", codec, len(data)) }
	// SSML path
    if _, _, err := p.Synthesize(context.Background(), "<speak>hi</speak>", SynthesisOptions{VoiceID: "en-US-Standard-A", Codec: "mp3"}); err != nil { t.Fatal(err) }
}
