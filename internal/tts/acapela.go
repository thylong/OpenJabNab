package tts

import (
	"context"
	"encoding/json"
	"errors"
	"io"
	"mime"
	"net/http"
	"net/url"
	"os"
	"path"
	"strings"
	"sync"
	"time"
)

type acapelaProvider struct{
	mu sync.Mutex
	baseURL string
	login   string
	password string
	application string
	httpClient *http.Client
}

func init() {
	Register("acapela", &acapelaProvider{httpClient: &http.Client{Timeout: 20 * time.Second}})
}

func (a *acapelaProvider) ensureConfig() error {
	a.mu.Lock(); defer a.mu.Unlock()
	if a.baseURL != "" { return nil }
	a.baseURL = os.Getenv("ACAPELA_BASE_URL")
	if a.baseURL == "" { a.baseURL = "https://api.acapela-group.com/v1" }
	a.login = os.Getenv("ACAPELA_LOGIN")
	a.password = os.Getenv("ACAPELA_PASSWORD")
	a.application = os.Getenv("ACAPELA_APPLICATION")
	if a.login == "" || a.password == "" || a.application == "" {
		return errors.New("acapela credentials not set (ACAPELA_LOGIN/ACAPELA_PASSWORD/ACAPELA_APPLICATION)")
	}
	return nil
}

func (a *acapelaProvider) ListVoices(ctx context.Context) ([]Voice, error) {
	_ = a.ensureConfig() // ignore missing creds for listing attempt
	req, _ := http.NewRequestWithContext(ctx, http.MethodGet, a.baseURL+"/voices", nil)
	resp, err := a.httpClient.Do(req)
	if err == nil && resp.StatusCode/100 == 2 {
		defer resp.Body.Close()
		var out struct{ Voices []struct{ Name string `json:"name"`; Language string `json:"language"`; Gender string `json:"gender"` } `json:"voices"` }
		if json.NewDecoder(resp.Body).Decode(&out) == nil {
			res := make([]Voice, 0, len(out.Voices))
			for _, v := range out.Voices { res = append(res, Voice{ID: v.Name, Language: v.Language, Name: v.Name, Gender: v.Gender}) }
			return res, nil
		}
	}
	// Fallback minimal known voices
	return []Voice{
		{ID: "enu_william", Language: "en-US", Name: "William", Gender: "M"},
		{ID: "fra_antoine", Language: "fr-FR", Name: "Antoine", Gender: "M"},
	}, nil
}

func (a *acapelaProvider) Synthesize(ctx context.Context, text string, voiceID string) ([]byte, string, error) {
	if err := a.ensureConfig(); err != nil { return nil, "", err }
	form := url.Values{}
	form.Set("login", a.login)
	form.Set("password", a.password)
	form.Set("application", a.application)
	form.Set("voice", voiceID)
	form.Set("text", text)
	form.Set("format", "mp3")
	endpoint := a.baseURL + "/synthesize"
    var resp *http.Response
    var err error
    backoff := 100 * time.Millisecond
    for attempt := 0; attempt < 2; attempt++ {
        req, _ := http.NewRequestWithContext(ctx, http.MethodPost, endpoint, strings.NewReader(form.Encode()))
        req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
        resp, err = a.httpClient.Do(req)
        if err != nil { return nil, "", err }
        if resp.StatusCode/100 == 2 { break }
        if attempt == 1 { // last attempt
            b, _ := io.ReadAll(resp.Body); resp.Body.Close()
            return nil, "", errors.New("acapela synth failed: "+string(b))
        }
        resp.Body.Close()
        select { case <-time.After(backoff): case <-ctx.Done(): return nil, "", ctx.Err() }
        backoff *= 2
    }
    defer resp.Body.Close()
	ct := resp.Header.Get("Content-Type")
	mediatype, _, _ := mime.ParseMediaType(ct)
	if strings.HasPrefix(mediatype, "audio/") {
		data, err := io.ReadAll(resp.Body)
		if err != nil { return nil, "", err }
		return data, extFromContentType(mediatype), nil
	}
	// Expect JSON with snd_url
	var out struct{ URL string `json:"snd_url"` }
	if err := json.NewDecoder(resp.Body).Decode(&out); err != nil { return nil, "", err }
	if out.URL == "" { return nil, "", errors.New("acapela: empty snd_url") }
	u, err := url.Parse(out.URL)
	if err != nil { return nil, "", err }
	req2, _ := http.NewRequestWithContext(ctx, http.MethodGet, u.String(), nil)
	resp2, err := a.httpClient.Do(req2)
	if err != nil { return nil, "", err }
	defer resp2.Body.Close()
	if resp2.StatusCode/100 != 2 { b, _ := io.ReadAll(resp2.Body); return nil, "", errors.New("download failed: "+string(b)) }
	data, err := io.ReadAll(resp2.Body)
	if err != nil { return nil, "", err }
	ct2 := resp2.Header.Get("Content-Type")
	mt2, _, _ := mime.ParseMediaType(ct2)
	return data, extFromContentType(mt2), nil
}

func extFromContentType(mt string) string {
	if mt == "audio/mpeg" || mt == "audio/mp3" { return "mp3" }
	if mt == "audio/wav" || mt == "audio/x-wav" { return "wav" }
	if mt == "audio/ogg" { return "ogg" }
	// derive from path if available
	if i := strings.LastIndex(mt, "/"); i >= 0 { return path.Ext(mt[i+1:]) }
	return "mp3"
}
