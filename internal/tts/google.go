package tts

import (
    "context"
    "crypto"
    "crypto/rand"
    "crypto/rsa"
    "crypto/sha256"
    "crypto/x509"
    "encoding/base64"
    "encoding/json"
    "encoding/pem"
    "errors"
    "io"
    "net/http"
    "net/url"
    "os"
    "strings"
    "sync"
    "time"
)

// googleProvider implements Provider using Google Cloud Text-to-Speech REST API.
// It authenticates with a service account JSON pointed by GOOGLE_APPLICATION_CREDENTIALS.
type googleProvider struct {
    mu        sync.Mutex
    token     string
    tokenExp  time.Time
    creds     serviceAccount
    httpClient *http.Client
    baseURL   string
}

type serviceAccount struct {
    Type        string `json:"type"`
    ClientEmail string `json:"client_email"`
    PrivateKey  string `json:"private_key"`
    TokenURI    string `json:"token_uri"`
}

func init() {
    // Best-effort register; initialization deferred until first use
    Register("google", &googleProvider{httpClient: &http.Client{Timeout: 15 * time.Second}})
}

func (g *googleProvider) ensureCreds() error {
    if g.creds.ClientEmail != "" { return nil }
    path := os.Getenv("GOOGLE_APPLICATION_CREDENTIALS")
    if path == "" {
        return errors.New("GOOGLE_APPLICATION_CREDENTIALS not set")
    }
    f, err := os.Open(path)
    if err != nil { return err }
    defer f.Close()
    dec := json.NewDecoder(f)
    if err := dec.Decode(&g.creds); err != nil { return err }
    if g.creds.TokenURI == "" { g.creds.TokenURI = "https://oauth2.googleapis.com/token" }
    return nil
}

func (g *googleProvider) getToken(ctx context.Context) (string, error) {
    g.mu.Lock(); defer g.mu.Unlock()
    if g.token != "" && time.Until(g.tokenExp) > 60*time.Second { return g.token, nil }
    if v := os.Getenv("GOOGLE_TTS_TOKEN"); v != "" {
        g.token = v; g.tokenExp = time.Now().Add(10 * time.Minute)
        return g.token, nil
    }
    if err := g.ensureCreds(); err != nil { return "", err }
    // Build JWT assertion
    header := base64.RawURLEncoding.EncodeToString([]byte(`{"alg":"RS256","typ":"JWT"}`))
    claims := map[string]interface{}{
        "iss": g.creds.ClientEmail,
        "scope": "https://www.googleapis.com/auth/cloud-platform",
        "aud": g.creds.TokenURI,
        "exp": time.Now().Add(1 * time.Hour).Unix(),
        "iat": time.Now().Unix(),
    }
    cb, _ := json.Marshal(claims)
    payload := base64.RawURLEncoding.EncodeToString(cb)
    signingInput := header + "." + payload
    // Parse private key (may contain escaped newlines)
    pkpem := g.creds.PrivateKey
    pkpem = strings.ReplaceAll(pkpem, "\\n", "\n")
    block, _ := pem.Decode([]byte(pkpem))
    if block == nil { return "", errors.New("invalid private key pem") }
    var priv *rsa.PrivateKey
    if key, err := x509.ParsePKCS8PrivateKey(block.Bytes); err == nil {
        if k, ok := key.(*rsa.PrivateKey); ok { priv = k } else { return "", errors.New("private key is not RSA") }
    } else if k, err2 := x509.ParsePKCS1PrivateKey(block.Bytes); err2 == nil {
        priv = k
    } else { return "", err }
    h := sha256.Sum256([]byte(signingInput))
    sig, err := rsa.SignPKCS1v15(rand.Reader, priv, crypto.SHA256, h[:])
    if err != nil { return "", err }
    assertion := signingInput + "." + base64.RawURLEncoding.EncodeToString(sig)
    // Exchange for access token
    form := url.Values{}
    form.Set("grant_type", "urn:ietf:params:oauth:grant-type:jwt-bearer")
    form.Set("assertion", assertion)
    req, _ := http.NewRequestWithContext(ctx, http.MethodPost, g.creds.TokenURI, strings.NewReader(form.Encode()))
    req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
    resp, err := g.httpClient.Do(req)
    if err != nil { return "", err }
    defer resp.Body.Close()
    if resp.StatusCode/100 != 2 { b, _ := io.ReadAll(resp.Body); return "", errors.New("token exchange failed: "+string(b)) }
    var tok struct{ AccessToken string `json:"access_token"`; ExpiresIn int `json:"expires_in"` }
    if err := json.NewDecoder(resp.Body).Decode(&tok); err != nil { return "", err }
    g.token = tok.AccessToken
    if tok.ExpiresIn <= 0 { tok.ExpiresIn = 3600 }
    g.tokenExp = time.Now().Add(time.Duration(tok.ExpiresIn) * time.Second)
    return g.token, nil
}

func (g *googleProvider) ListVoices(ctx context.Context) ([]Voice, error) {
    tok, err := g.getToken(ctx)
    if err != nil { return nil, err }
    if g.baseURL == "" { g.baseURL = os.Getenv("GOOGLE_TTS_ENDPOINT"); if g.baseURL == "" { g.baseURL = "https://texttospeech.googleapis.com" } }
    req, _ := http.NewRequestWithContext(ctx, http.MethodGet, g.baseURL+"/v1/voices", nil)
    req.Header.Set("Authorization", "Bearer "+tok)
    resp, err := g.httpClient.Do(req)
    if err != nil { return nil, err }
    defer resp.Body.Close()
    if resp.StatusCode/100 != 2 { return nil, errors.New("google voices failed: "+resp.Status) }
    var out struct{ Voices []struct{ Name string `json:"name"`; LanguageCodes []string `json:"languageCodes"`; SsmlGender string `json:"ssmlGender"` } `json:"voices"` }
    if err := json.NewDecoder(resp.Body).Decode(&out); err != nil { return nil, err }
    res := make([]Voice, 0, len(out.Voices))
    for _, v := range out.Voices {
        lang := ""
        if len(v.LanguageCodes) > 0 { lang = v.LanguageCodes[0] }
        res = append(res, Voice{ID: v.Name, Language: lang, Name: v.Name, Gender: v.SsmlGender})
    }
    return res, nil
}

func (g *googleProvider) Synthesize(ctx context.Context, text string, voiceID string) ([]byte, string, error) {
    tok, err := g.getToken(ctx)
    if err != nil { return nil, "", err }
    if g.baseURL == "" { g.baseURL = os.Getenv("GOOGLE_TTS_ENDPOINT"); if g.baseURL == "" { g.baseURL = "https://texttospeech.googleapis.com" } }
    body := map[string]any{
        "input": map[string]string{"text": text},
        "voice": map[string]string{"name": voiceID},
        "audioConfig": map[string]string{"audioEncoding": "MP3"},
    }
    if strings.Contains(strings.ToLower(text), "<speak") {
        body["input"] = map[string]string{"ssml": text}
    }
    b, _ := json.Marshal(body)
    req, _ := http.NewRequestWithContext(ctx, http.MethodPost, g.baseURL+"/v1/text:synthesize", strings.NewReader(string(b)))
    req.Header.Set("Authorization", "Bearer "+tok)
    req.Header.Set("Content-Type", "application/json")
    resp, err := g.httpClient.Do(req)
    if err != nil { return nil, "", err }
    defer resp.Body.Close()
    if resp.StatusCode/100 != 2 { return nil, "", errors.New("google synth failed: "+resp.Status) }
    var out struct{ AudioContent string `json:"audioContent"` }
    if err := json.NewDecoder(resp.Body).Decode(&out); err != nil { return nil, "", err }
    data, err := base64.StdEncoding.DecodeString(out.AudioContent)
    if err != nil { return nil, "", err }
    return data, "mp3", nil
}
