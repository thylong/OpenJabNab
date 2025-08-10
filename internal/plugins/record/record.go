package record

import (
    "regexp"
    "strings"
    "time"
    "sync"

    cfgpkg "OpenJabNab/internal/config"
    p "OpenJabNab/internal/plugin"
)

type Plugin struct{
    enabled  bool
    settings *p.Settings
    mu       sync.Mutex
}

func New(cfg *cfgpkg.Config) *Plugin {
    st, _ := p.NewSettings(cfg.PluginsDir, "record")
    return &Plugin{enabled: true, settings: st}
}

func (pl *Plugin) Name() string { return "record" }
func (pl *Plugin) Type() p.PluginType { return p.BunnyPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}
func (pl *Plugin) HttpRequestHandle(r *p.Request) bool { return false }

// Minimal recording control API (no actual audio handling):
// - start?bunny=ID[&seconds=N]  → <ok/>
// - stop?bunny=ID               → <ok/>
// - status?bunny=ID             → <recording>true/false</recording><seconds>N</seconds><started>RFC3339</started>
func (pl *Plugin) ProcessPluginApi(function string, get map[string]string) (bool, []byte, error) {
    switch strings.ToLower(function) {
    case "start":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        secs := get["seconds"]; if secs == "" { secs = "30" }
        if !isValidSeconds(secs) { return true, []byte(`<error>Invalid seconds</error>`), nil }
        sec := "bunny_" + id
        _ = pl.settings.Set(sec, "recording", "true")
        _ = pl.settings.Set(sec, "seconds", secs)
        _ = pl.settings.Set(sec, "started", time.Now().UTC().Format(time.RFC3339))
        return true, []byte(`<ok/>`), nil
    case "stop":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        sec := "bunny_" + id
        _ = pl.settings.Set(sec, "recording", "false")
        return true, []byte(`<ok/>`), nil
    case "status":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        sec := "bunny_" + id
        rec := pl.settings.Get(sec, "recording", "false")
        secs := pl.settings.Get(sec, "seconds", "0")
        started := pl.settings.Get(sec, "started", "")
        inner := `<recording>` + xmlEscape(rec) + `</recording>` +
            `<seconds>` + xmlEscape(secs) + `</seconds>`
        if started != "" { inner += `<started>` + xmlEscape(started) + `</started>` }
        return true, []byte(inner), nil
    }
    return false, nil, nil
}

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

var secsRe = regexp.MustCompile(`^[0-9]{1,5}$`)
func isValidSeconds(s string) bool { return secsRe.MatchString(s) }
