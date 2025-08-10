package tts

import (
    "context"
    "crypto/sha1"
    "encoding/hex"
    "os"
    "path/filepath"
    "regexp"
    "strings"
    "sync"
    "time"

    cfgpkg "OpenJabNab/internal/config"
    p "OpenJabNab/internal/plugin"
    prov "OpenJabNab/internal/tts"
)

type Plugin struct{
    enabled    bool
    settings   *p.Settings
    mu         sync.Mutex
    queue      chan speakJob
    provider   prov.Provider
    outputRoot string // filesystem root where broadcast files are written
    // path under broadcast for tts files: broadcast/tts/<voice>/<hash>.mp3
    send       func(bunnyID string, payload []byte) bool
    // rate limiting
    rateInterval time.Duration
    rateBurst    int
    tokens       chan struct{}
}

type speakJob struct{
    bunnyID string
    text    string
    voiceID string
}

func New(cfg *cfgpkg.Config) *Plugin {
    st, _ := p.NewSettings(cfg.PluginsDir, "tts")
    // Choose provider from config; fallback to mock
    provider := prov.Get(cfg.TTS)
    // Determine output root
    out := filepath.Join("state", "broadcast")
    if cfg.RealHttpRoot != "" {
        // RealHttpRoot already ends with a path to http root (e.g., ../http-wrapper/ojn_local/)
        out = filepath.Join(cfg.RealHttpRoot, "broadcast")
    }
    pl := &Plugin{enabled: true, settings: st, queue: make(chan speakJob, 64), provider: provider, outputRoot: out}
    // init rate limiter
    rps := cfg.TTSRateLimitRPS
    if rps <= 0 { rps = 5 }
    pl.rateInterval = time.Second / time.Duration(rps)
    pl.rateBurst = rps
    pl.tokens = make(chan struct{}, pl.rateBurst)
    go func(){
        ticker := time.NewTicker(pl.rateInterval)
        defer ticker.Stop()
        for range ticker.C {
            select { case pl.tokens <- struct{}{}: default: }
        }
    }()
    go pl.worker()
    return pl
}

func (pl *Plugin) Name() string { return "tts" }
func (pl *Plugin) VisualName() string { return "TTS" }
func (pl *Plugin) Type() p.PluginType { return p.BunnyPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}
func (pl *Plugin) HttpRequestHandle(r *p.Request) bool { return false }

// API:
// - voices                                 → <list><item id=.. lang=.. gender=..>name</item>...</list>
// - setvoice?bunny=ID&voice=ID             → <ok/>
// - getvoice?bunny=ID                      → <voice>ID</voice>
// - speak?bunny=ID&text=... [&voice=ID]    → <ok/>
// - queue?bunny=ID                         → <queue/> (not persisted; optional)
// - clear?bunny=ID                         → <ok/>
func (pl *Plugin) ProcessPluginApi(function string, get map[string]string) (bool, []byte, error) {
    switch strings.ToLower(function) {
    case "voices":
        vs, _ := pl.provider.ListVoices(context.Background())
        // Optional allowlist filter from config via settings key or env
        allow := pl.settings.Get("plugin", "AllowedVoices", "")
        if allow == "" { allow = pl.settings.Get("plugin", "allowedVoices", "") }
        if allow == "" { allow = "" } // no filter
        var filt map[string]struct{}
        if allow != "" {
            filt = map[string]struct{}{}
            for _, v := range strings.Split(allow, ",") { filt[strings.TrimSpace(v)] = struct{}{} }
        }
        inner := "<list>"
        for _, v := range vs {
            if filt != nil { if _, ok := filt[v.ID]; !ok { continue } }
            inner += "<item id=\""+xmlEscape(v.ID)+"\" lang=\""+xmlEscape(v.Language)+"\" gender=\""+xmlEscape(v.Gender)+"\">"+xmlEscape(v.Name)+"</item>"
        }
        inner += "</list>"
        return true, []byte(inner), nil
    case "setvoice":
        id := get["bunny"]; if id == "" { id = get["id"] }
        voice := strings.TrimSpace(get["voice"])
        if id == "" || voice == "" { return true, []byte(`<error>Missing bunny or voice</error>`), nil }
        if !isValidBunnyID(id) || !isValidVoice(voice) { return true, []byte(`<error>Invalid parameters</error>`), nil }
        _ = pl.settings.Set("bunny_"+id, "voice", voice)
        return true, []byte(`<ok/>`), nil
    case "getvoice":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        voice := pl.settings.Get("bunny_"+id, "voice", "")
        return true, []byte(`<voice>` + xmlEscape(voice) + `</voice>`), nil
    case "speak":
        id := get["bunny"]; if id == "" { id = get["id"] }
        text := strings.TrimSpace(get["text"])
        voice := strings.TrimSpace(get["voice"]) // optional override
        if id == "" || text == "" { return true, []byte(`<error>Missing bunny or text</error>`), nil }
        if !isValidBunnyID(id) || !isValidText(text) { return true, []byte(`<error>Invalid parameters</error>`), nil }
        if voice == "" { voice = pl.settings.Get("bunny_"+id, "voice", pl.settings.Get("plugin", "DefaultVoice", "en-US-Standard-A")) }
        // Basic enqueue
        select { case pl.queue <- speakJob{bunnyID: id, text: text, voiceID: voice}: default: }
        return true, []byte(`<ok/>`), nil
    case "queue":
        // not implemented as persisted; return empty
        return true, []byte(`<queue/>`), nil
    case "clear":
        return true, []byte(`<ok/>`), nil
    }
    return false, nil, nil
}

func (pl *Plugin) worker() {
    for job := range pl.queue {
        // rate limit
        select { case <-pl.tokens: default: time.Sleep(pl.rateInterval) }
        // Synthesize
        timeout := time.Duration(15000) * time.Millisecond
        if ms := pl.settings.Get("plugin", "TimeoutMs", ""); ms != "" { /* allow per-plugin override via ini */ }
        ctx, cancel := context.WithTimeout(context.Background(), timeout)
        data, codec, err := pl.trySynthesize(ctx, job.text, job.voiceID)
        cancel()
        if err != nil { continue }
        // Compute path and write
        h := sha1.Sum([]byte("mock:" + job.voiceID + ":" + codec + ":" + job.text))
        key := hex.EncodeToString(h[:])
        rel := filepath.Join("tts", sanitize(job.voiceID), key+"."+sanitize(codec))
        full := filepath.Join(pl.outputRoot, rel)
        _ = os.MkdirAll(filepath.Dir(full), 0o755)
        tmp := full + ".tmp"
        if err := os.WriteFile(tmp, data, 0o644); err == nil {
            _ = os.Rename(tmp, full)
        }
        // Send play packet if available
        if pl.send != nil {
            path := filepath.ToSlash(filepath.Join("broadcast", rel))
            msg := []byte("MU " + path + "\nMW\n")
            _ = pl.send(job.bunnyID, msg)
        }
    }
}

func (pl *Plugin) trySynthesize(ctx context.Context, text, voice string) ([]byte, string, error) {
    // retries with backoff
    maxRetries := 2
    backoff := 200 * time.Millisecond
    var data []byte
    var codec string
    var err error
    for attempt := 0; attempt <= maxRetries; attempt++ {
        data, codec, err = pl.provider.Synthesize(ctx, text, voice)
        if err == nil { return data, codec, nil }
        select { case <-time.After(backoff): case <-ctx.Done(): return nil, "", ctx.Err() }
        backoff *= 2
    }
    return nil, "", err
}

// Implement PacketSenderAware
func (pl *Plugin) SetPacketSender(fn func(bunnyID string, payload []byte) bool) { pl.send = fn }

func xmlEscape(s string) string {
    r := strings.NewReplacer(
        "&", "&amp;",
        "<", "&lt;",
        ">", "&gt;",
        "\"", "&quot;",
        "'", "&apos;",
    )
    return r.Replace(s)
}

var bunnyRe = regexp.MustCompile(`^[A-Za-z0-9_-]{1,64}$`)
func isValidBunnyID(id string) bool { return bunnyRe.MatchString(id) }

var voiceRe = regexp.MustCompile(`^[A-Za-z0-9 _.-]{1,64}$`)
func isValidVoice(v string) bool { return voiceRe.MatchString(v) }

func isValidText(t string) bool {
    if len(t) == 0 || len(t) > 512 { return false }
    for i := 0; i < len(t); i++ { if t[i] < 0x20 && t[i] != 0x09 && t[i] != 0x0A && t[i] != 0x0D { return false } }
    return true
}

func sanitize(s string) string {
    out := make([]rune, 0, len(s))
    for _, r := range s {
        if (r >= 'a' && r <= 'z') || (r >= 'A' && r <= 'Z') || (r >= '0' && r <= '9') || r=='-' || r=='_' || r=='.' { out = append(out, r) }
    }
    if len(out) == 0 { return "x" }
    return string(out)
}
