package tts

import (
    "context"
    "crypto/sha1"
    "encoding/hex"
    "encoding/json"
    "os"
    "path/filepath"
    // stdpath "path"
    "regexp"
    "strconv"
    "strings"
    "sync"
    "time"

    cfgpkg "OpenJabNab/internal/config"
    p "OpenJabNab/internal/plugin"
    prov "OpenJabNab/internal/tts"
    "log/slog"
)

type Plugin struct{
    enabled    bool
    settings   *p.Settings
    mu         sync.Mutex
    queue      chan speakJob
    provider   prov.Provider
    providerName string
    outputRoot string // filesystem root where broadcast files are written
    // path under broadcast for tts files: broadcast/tts/<voice>/<hash>.mp3
    send       func(bunnyID string, payload []byte) bool
    // Optional absolute URL base for MU command (e.g., http://r.nabaztag.com)
    muBaseURL string
    // rate limiting
    rateInterval time.Duration
    rateBurst    int
    tokens       chan struct{}

    // jobs and metrics
    jobsMu   sync.Mutex
    jobs     map[string]jobStatus
    nextSeq  uint64
    metricMu sync.Mutex
    queued   uint64
    running  uint64
    completed uint64
    errors   uint64
    cacheHits uint64
    cacheMiss uint64
    synthNsTotal uint64
    synthCount   uint64

    // shutdown
    stopCh chan struct{}
    wg     sync.WaitGroup
    jobHistoryMax int

    // config for retries/timeouts
    timeout    time.Duration
    maxRetries int
    backoff    time.Duration
    // persistence
    statePath string
}

type speakJob struct{
    bunnyID string
    text    string
    voiceID string
    id      string
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
    pname := cfg.TTS
    if pname == "" { pname = "mock" }
    qsize := cfg.TTSQueueSize
    if qsize <= 0 { qsize = 128 }
    pl := &Plugin{enabled: true, settings: st, queue: make(chan speakJob, qsize), provider: provider, providerName: pname, outputRoot: out, jobs: make(map[string]jobStatus), stopCh: make(chan struct{}), jobHistoryMax: cfg.TTSJobHistoryMax}
    // Build absolute MU base from BroadServer if provided
    if host := strings.TrimSpace(cfg.OpenJabNabServers.BroadServer); host != "" {
        // If host already contains scheme, keep it. Otherwise default to http://
        if strings.HasPrefix(host, "http://") || strings.HasPrefix(host, "https://") {
            pl.muBaseURL = strings.TrimRight(host, "/")
        } else {
            pl.muBaseURL = "http://" + strings.TrimRight(host, "/")
        }
    }
    // retry/timeout config
    if cfg.TTSTimeoutMs <= 0 { cfg.TTSTimeoutMs = 15000 }
    pl.timeout = time.Duration(cfg.TTSTimeoutMs) * time.Millisecond
    if cfg.TTSMaxRetries < 0 { cfg.TTSMaxRetries = 2 }
    pl.maxRetries = cfg.TTSMaxRetries
    if cfg.TTSBackoffMs <= 0 { cfg.TTSBackoffMs = 200 }
    pl.backoff = time.Duration(cfg.TTSBackoffMs) * time.Millisecond
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
    // persistence path and load
    if cfg.StateDir != "" {
        pl.statePath = filepath.Join(cfg.StateDir, "tts_jobs.json")
        pl.loadJobs()
    }
    // start workers
    workers := cfg.TTSWorkers
    if workers <= 0 { workers = 2 }
    for i := 0; i < workers; i++ { pl.wg.Add(1); go pl.worker() }
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
        // Mask provider errors; short timeout
        ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
        defer cancel()
        vs, err := pl.provider.ListVoices(ctx)
        if err != nil { return true, []byte(`<error>Unavailable</error>`), nil }
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
        // Enqueue with backpressure; if full return 429-like error
        jid := pl.newJobID(id)
        pl.jobsMu.Lock()
        pl.jobs[jid] = jobStatus{ID: jid, Bunny: id, Voice: voice, TextHash: shortHash(normalizeText(text)), Text: text, State: "queued", EnqueuedAt: time.Now()}
        pl.jobsMu.Unlock()
        select {
        case pl.queue <- speakJob{bunnyID: id, text: text, voiceID: voice, id: jid}:
            pl.metricMu.Lock(); pl.queued++; pl.metricMu.Unlock()
            return true, []byte(`<ok id="`+jid+`"/>`), nil
        default:
            // mark as dropped
            pl.jobsMu.Lock(); st := pl.jobs[jid]; st.State = "dropped"; st.Error = "queue_full"; pl.jobs[jid] = st; pl.jobsMu.Unlock()
            return true, []byte(`<error>Too busy, try later</error>`), nil
        }
    case "queue":
        // return queue size and capacity
        size := len(pl.queue)
        capc := cap(pl.queue)
        return true, []byte(`<queue size="`+strconvItoa(size)+`" capacity="`+strconvItoa(capc)+`"/>`), nil
    case "clear":
        pl.jobsMu.Lock(); pl.jobs = make(map[string]jobStatus); pl.jobsMu.Unlock()
        return true, []byte(`<ok/>`), nil
    case "status":
        jid := strings.TrimSpace(get["id"])
        if jid == "" { return true, []byte(`<error>Missing id</error>`), nil }
        pl.jobsMu.Lock(); st, ok := pl.jobs[jid]; pl.jobsMu.Unlock()
        if !ok { return true, []byte(`<error>Unknown id</error>`), nil }
        return true, []byte(st.toXML()), nil
    case "stats":
        pl.metricMu.Lock()
        q, r, c, e, ch, cm, sn, sc := pl.queued, pl.running, pl.completed, pl.errors, pl.cacheHits, pl.cacheMiss, pl.synthNsTotal, pl.synthCount
        pl.metricMu.Unlock()
        avg := 0
        if sc > 0 { avg = int(sn / sc) }
        return true, []byte(`<stats>`+
            `<queued>`+strconvIota(int(q))+`</queued>`+
            `<running>`+strconvIota(int(r))+`</running>`+
            `<completed>`+strconvIota(int(c))+`</completed>`+
            `<errors>`+strconvIota(int(e))+`</errors>`+
            `<cache_hits>`+strconvIota(int(ch))+`</cache_hits>`+
            `<cache_miss>`+strconvIota(int(cm))+`</cache_miss>`+
            `<avg_synth_ns>`+strconvIota(avg)+`</avg_synth_ns>`+
        `</stats>`), nil
    case "health":
        // Lightweight health: attempt a quick ListVoices call with short timeout
        ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
        defer cancel()
        if _, err := pl.provider.ListVoices(ctx); err != nil {
            return true, []byte(`<status>degraded</status>`), nil
        }
        return true, []byte(`<status>ok</status>`), nil
    }
    return false, nil, nil
}

func (pl *Plugin) worker() {
    defer pl.wg.Done()
    for {
        var job speakJob
        select {
        case <-pl.stopCh:
            return
        case job = <-pl.queue:
        }
        // rate limit
        select { case <-pl.tokens: default: time.Sleep(pl.rateInterval) }
        // Compute cache key and potential target path(s)
        nt := normalizeText(job.text)
        sum := sha1.Sum([]byte(pl.providerName + ":" + job.voiceID + ":" + nt))
        key := hex.EncodeToString(sum[:])
        // Attempt cache lookup for common codecs
        cacheHit := false
        var rel string
        for _, ext := range []string{"mp3", "wav", "ogg"} {
            rel = filepath.Join("tts", sanitize(job.voiceID), key+"."+ext)
            full := filepath.Join(pl.outputRoot, rel)
            if _, err := os.Stat(full); err == nil { cacheHit = true; break }
        }
        if cacheHit {
            if pl.send != nil {
                path := filepath.ToSlash(filepath.Join("broadcast", rel))
                target := path
                if pl.muBaseURL != "" { target = pl.muBaseURL + "/" + path }
                msg := []byte("MU " + target + "\nMW\nST\n")
                _ = pl.send(job.bunnyID, msg)
            }
            pl.metricMu.Lock(); pl.cacheHits++; pl.completed++; pl.metricMu.Unlock()
            pl.updateJobDone(job.id, filepath.ToSlash(filepath.Join("broadcast", rel)))
            slog.Default().Info("tts cache hit", slog.String("bunny", job.bunnyID), slog.String("voice", job.voiceID), slog.String("key", key))
            continue
        }
        // Synthesize on miss
        ctx, cancel := context.WithTimeout(context.Background(), pl.timeout)
        pl.metricMu.Lock(); pl.running++; pl.metricMu.Unlock()
        start := time.Now()
        data, codec, err := pl.trySynthesize(ctx, job.text, job.voiceID)
        cancel()
        dur := time.Since(start)
        pl.metricMu.Lock(); pl.running--;
        if err != nil { pl.errors++; pl.metricMu.Unlock(); pl.updateJobError(job.id, "synth_failed"); continue }
        pl.synthNsTotal += uint64(dur.Nanoseconds()); pl.synthCount++; pl.metricMu.Unlock()
        // Compute path and write using stable key (no codec in key; only in extension)
        rel = filepath.Join("tts", sanitize(job.voiceID), key+"."+sanitize(codec))
        full := filepath.Join(pl.outputRoot, rel)
        _ = os.MkdirAll(filepath.Dir(full), 0o755)
        tmp := full + ".tmp"
        if err := os.WriteFile(tmp, data, 0o644); err == nil {
            _ = os.Rename(tmp, full)
        }
        // Send play packet if available
        if pl.send != nil {
            path := filepath.ToSlash(filepath.Join("broadcast", rel))
            target := path
            if pl.muBaseURL != "" { target = pl.muBaseURL + "/" + path }
            msg := []byte("MU " + target + "\nMW\nST\n")
            _ = pl.send(job.bunnyID, msg)
        }
        pl.metricMu.Lock(); pl.cacheMiss++; pl.completed++; pl.metricMu.Unlock()
        pl.updateJobDone(job.id, filepath.ToSlash(filepath.Join("broadcast", rel)))
        slog.Default().Info("tts cache miss", slog.String("bunny", job.bunnyID), slog.String("voice", job.voiceID), slog.String("key", key), slog.String("codec", codec))
    }
}

type jobStatus struct{
    ID string
    Bunny string
    Voice string
    TextHash string
    Text string `json:"text,omitempty"`
    State string // queued|running|done|error|dropped
    EnqueuedAt time.Time
    StartedAt  time.Time
    CompletedAt time.Time
    URL string
    Error string
}

func (s jobStatus) toXML() string {
    b := strings.Builder{}
    b.WriteString(`<job id="`+s.ID+`"><state>`+s.State+`</state>`)
    if s.URL != "" { b.WriteString(`<url>`+s.URL+`</url>`) }
    if s.Error != "" { b.WriteString(`<error>`+s.Error+`</error>`) }
    b.WriteString(`</job>`)
    return b.String()
}

func (pl *Plugin) newJobID(bunny string) string {
    pl.jobsMu.Lock(); defer pl.jobsMu.Unlock()
    pl.nextSeq++
    return sanitize(bunny) + "-" + strconvIota(int(pl.nextSeq))
}

func (pl *Plugin) updateJobDone(id, urlPath string) {
    pl.jobsMu.Lock(); defer pl.jobsMu.Unlock()
    st, ok := pl.jobs[id]
    if !ok { return }
    st.State = "done"
    st.CompletedAt = time.Now()
    st.URL = urlPath
    pl.jobs[id] = st
    pl.pruneJobsLocked()
}

func (pl *Plugin) updateJobError(id, code string) {
    pl.jobsMu.Lock(); defer pl.jobsMu.Unlock()
    st, ok := pl.jobs[id]
    if !ok { return }
    st.State = "error"
    st.Error = code
    st.CompletedAt = time.Now()
    pl.jobs[id] = st
    pl.pruneJobsLocked()
}

func shortHash(s string) string {
    sum := sha1.Sum([]byte(s))
    return hex.EncodeToString(sum[:4])
}

// Shutdown signals workers to stop after draining the queue, and waits for them.
func (pl *Plugin) Shutdown() {
    close(pl.stopCh)
    pl.wg.Wait()
    pl.jobsMu.Lock(); pl.saveJobsLocked(); pl.jobsMu.Unlock()
}

func (pl *Plugin) pruneJobsLocked() {
    max := pl.jobHistoryMax
    if max <= 0 { max = 1000 }
    if len(pl.jobs) <= max { return }
    // naive prune: remove oldest by CompletedAt
    oldestID := ""
    oldestTime := time.Now()
    for id, st := range pl.jobs { if !st.CompletedAt.IsZero() && st.CompletedAt.Before(oldestTime) { oldestTime = st.CompletedAt; oldestID = id } }
    if oldestID != "" { delete(pl.jobs, oldestID) }
}

// persistence helpers
func (pl *Plugin) saveJobsLocked() {
    if pl.statePath == "" { return }
    items := make([]jobStatus, 0, len(pl.jobs))
    for _, st := range pl.jobs { items = append(items, st) }
    tmp := pl.statePath + ".tmp"
    b, _ := json.Marshal(items)
    _ = os.MkdirAll(filepath.Dir(pl.statePath), 0o755)
    if err := os.WriteFile(tmp, b, 0o644); err == nil { _ = os.Rename(tmp, pl.statePath) }
}

func (pl *Plugin) loadJobs() {
    if pl.statePath == "" { return }
    b, err := os.ReadFile(pl.statePath)
    if err != nil || len(b) == 0 { return }
    var items []jobStatus
    if json.Unmarshal(b, &items) != nil { return }
    for _, st := range items {
        pl.jobs[st.ID] = st
        if (st.State == "queued" || st.State == "running") && st.Text != "" {
            select { case pl.queue <- speakJob{bunnyID: st.Bunny, text: st.Text, voiceID: st.Voice, id: st.ID}: default: }
        }
        // best-effort: update nextSeq from id suffix if numeric
        parts := strings.Split(st.ID, "-")
        if len(parts) > 1 {
            if n, err := strconv.Atoi(parts[len(parts)-1]); err == nil && uint64(n) > pl.nextSeq { pl.nextSeq = uint64(n) }
        }
    }
}

func (pl *Plugin) trySynthesize(ctx context.Context, text, voice string) ([]byte, string, error) {
    // retries with backoff
    maxRetries := pl.maxRetries
    backoff := pl.backoff
    var data []byte
    var codec string
    var err error
    for attempt := 0; attempt <= maxRetries; attempt++ {
        data, codec, err = pl.provider.Synthesize(ctx, text, prov.SynthesisOptions{VoiceID: voice, Codec: "mp3"})
        if err == nil { return data, codec, nil }
        select { case <-time.After(backoff): case <-ctx.Done(): return nil, "", ctx.Err() }
        backoff *= 2
    }
    return nil, "", err
}

// Cleanup removes old or over-quota cache files under outputRoot/broadcast/tts
func (pl *Plugin) Cleanup(retentionDays int, maxCacheMB int) {
    root := filepath.Join(pl.outputRoot, "tts")
    // Retention-based cleanup
    if retentionDays > 0 {
        cutoff := time.Now().Add(-time.Duration(retentionDays) * 24 * time.Hour)
        filepath.Walk(root, func(p string, info os.FileInfo, err error) error {
            if err != nil || info == nil || info.IsDir() { return nil }
            if info.ModTime().Before(cutoff) { _ = os.Remove(p) }
            return nil
        })
    }
    // Size-based cleanup (best-effort): if maxCacheMB > 0, remove oldest until under limit
    if maxCacheMB > 0 {
        var files []os.FileInfo
        var paths []string
        var total int64
        filepath.Walk(root, func(p string, info os.FileInfo, err error) error {
            if err != nil || info == nil || info.IsDir() { return nil }
            files = append(files, info); paths = append(paths, p); total += info.Size(); return nil
        })
        limit := int64(maxCacheMB) * 1024 * 1024
        if total > limit {
            // sort by ModTime asc (oldest first)
            for i := 0; i < len(files)-1; i++ {
                for j := i+1; j < len(files); j++ {
                    if files[i].ModTime().After(files[j].ModTime()) {
                        files[i], files[j] = files[j], files[i]
                        paths[i], paths[j] = paths[j], paths[i]
                    }
                }
            }
            for i := 0; i < len(files) && total > limit; i++ {
                _ = os.Remove(paths[i]); total -= files[i].Size()
            }
        }
    }
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

func normalizeText(s string) string {
    // Trim spaces and collapse internal whitespace minimally for cache key stability
    s = strings.TrimSpace(s)
    // Replace CRLF with LF
    s = strings.ReplaceAll(s, "\r\n", "\n")
    return s
}
