package webradio

import (
    "net/url"
    "regexp"
    "strings"
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
    st, _ := p.NewSettings(cfg.PluginsDir, "webradio")
    return &Plugin{enabled: true, settings: st}
}

func (pl *Plugin) Name() string { return "webradio" }
func (pl *Plugin) Type() p.PluginType { return p.BunnyPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}
func (pl *Plugin) HttpRequestHandle(r *p.Request) bool { return false }

// ProcessPluginApi implements minimal station management and playback state (no actual streaming here).
// Functions:
// - list                                → <list><item name="..">url</item>...</list>
// - add?name=...&url=...                → <ok/>
// - del?name=...                        → <ok/>
// - set?bunny=ID&name=...               → <ok/>
// - status?bunny=ID                     → <playing>true/false</playing><station>name</station>
// - play?bunny=ID                       → <ok/>
// - stop?bunny=ID                       → <ok/>
func (pl *Plugin) ProcessPluginApi(function string, get map[string]string) (bool, []byte, error) {
    switch strings.ToLower(function) {
    case "list":
        keys := pl.settings.Keys("stations")
        inner := "<list>"
        for _, k := range keys {
            url := pl.settings.Get("stations", k, "")
            inner += "<item name=\"" + xmlEscape(k) + "\">" + xmlEscape(url) + "</item>"
        }
        inner += "</list>"
        return true, []byte(inner), nil
    case "add":
        name := strings.TrimSpace(get["name"]) 
        raw := strings.TrimSpace(get["url"]) 
        if name == "" || raw == "" { return true, []byte(`<error>Missing name or url</error>`), nil }
        if !isValidStationName(name) { return true, []byte(`<error>Invalid name</error>`), nil }
        if !isValidURL(raw) { return true, []byte(`<error>Invalid url</error>`), nil }
        _ = pl.settings.Set("stations", name, raw)
        return true, []byte(`<ok/>`), nil
    case "del":
        name := strings.TrimSpace(get["name"]) 
        if name == "" { return true, []byte(`<error>Missing name</error>`), nil }
        if !isValidStationName(name) { return true, []byte(`<error>Invalid name</error>`), nil }
        _ = pl.settings.Delete("stations", name)
        return true, []byte(`<ok/>`), nil
    case "set":
        id := get["bunny"]; if id == "" { id = get["id"] }
        name := strings.TrimSpace(get["name"]) 
        if id == "" || name == "" { return true, []byte(`<error>Missing bunny or name</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        if !isValidStationName(name) { return true, []byte(`<error>Invalid name</error>`), nil }
        // ensure station exists (optional)
        url := pl.settings.Get("stations", name, "")
        if url == "" { return true, []byte(`<error>Unknown station</error>`), nil }
        sec := "bunny_" + id
        _ = pl.settings.Set(sec, "station", name)
        return true, []byte(`<ok/>`), nil
    case "status":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        sec := "bunny_" + id
        station := pl.settings.Get(sec, "station", "")
        playing := pl.settings.Get(sec, "playing", "false")
        if station == "" { return true, []byte(`<playing>` + xmlEscape(playing) + `</playing><station/>`), nil }
        return true, []byte(`<playing>` + xmlEscape(playing) + `</playing><station>` + xmlEscape(station) + `</station>`), nil
    case "play":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        sec := "bunny_" + id
        _ = pl.settings.Set(sec, "playing", "true")
        return true, []byte(`<ok/>`), nil
    case "stop":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        sec := "bunny_" + id
        _ = pl.settings.Set(sec, "playing", "false")
        return true, []byte(`<ok/>`), nil
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

var nameRe = regexp.MustCompile(`^[A-Za-z0-9 _.-]{1,64}$`)
func isValidStationName(name string) bool {
    return nameRe.MatchString(name)
}

var bunnyRe = regexp.MustCompile(`^[A-Za-z0-9_-]{1,64}$`)
func isValidBunnyID(id string) bool { return bunnyRe.MatchString(id) }

func isValidURL(raw string) bool {
    u, err := url.Parse(raw)
    if err != nil { return false }
    if u.Scheme != "http" && u.Scheme != "https" { return false }
    if u.Host == "" { return false }
    return true
}
