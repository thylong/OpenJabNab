package tts

import (
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
	queue    map[string][]string // bunnyID -> texts (in-memory)
}

func New(cfg *cfgpkg.Config) *Plugin {
	st, _ := p.NewSettings(cfg.PluginsDir, "tts")
	return &Plugin{enabled: true, settings: st, queue: make(map[string][]string)}
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
// - voices                                 → <list><item>...</item>...</list>
// - setvoice?bunny=ID&voice=NAME           → <ok/>
// - getvoice?bunny=ID                      → <voice>NAME</voice>
// - speak?bunny=ID&text=...                → <ok/>
// - queue?bunny=ID                         → <queue><item>...</item>...</queue>
// - clear?bunny=ID                         → <ok/>
func (pl *Plugin) ProcessPluginApi(function string, get map[string]string) (bool, []byte, error) {
	switch strings.ToLower(function) {
	case "voices":
		keys := pl.settings.Keys("voices")
		inner := "<list>"
		for _, k := range keys { inner += "<item>" + xmlEscape(k) + "</item>" }
		inner += "</list>"
		return true, []byte(inner), nil
	case "setvoice":
		id := get["bunny"]; if id == "" { id = get["id"] }
		voice := strings.TrimSpace(get["voice"])
		if id == "" || voice == "" { return true, []byte(`<error>Missing bunny or voice</error>`), nil }
		if !isValidBunnyID(id) || !isValidVoice(voice) { return true, []byte(`<error>Invalid parameters</error>`), nil }
		// Ensure voice exists (optional)
		if len(pl.settings.Keys("voices")) > 0 {
			v := pl.settings.Get("voices", voice, "")
			if v == "" { return true, []byte(`<error>Unknown voice</error>`), nil }
		}
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
		if id == "" || text == "" { return true, []byte(`<error>Missing bunny or text</error>`), nil }
		if !isValidBunnyID(id) || !isValidText(text) { return true, []byte(`<error>Invalid parameters</error>`), nil }
		pl.mu.Lock()
		pl.queue[id] = append(pl.queue[id], text)
		pl.mu.Unlock()
		return true, []byte(`<ok/>`), nil
	case "queue":
		id := get["bunny"]; if id == "" { id = get["id"] }
		if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
		if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
		pl.mu.Lock(); items := append([]string(nil), pl.queue[id]...); pl.mu.Unlock()
		inner := "<queue>"
		for _, it := range items { inner += "<item>" + xmlEscape(it) + "</item>" }
		inner += "</queue>"
		return true, []byte(inner), nil
	case "clear":
		id := get["bunny"]; if id == "" { id = get["id"] }
		if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
		if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
		pl.mu.Lock(); delete(pl.queue, id); pl.mu.Unlock()
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

var bunnyRe = regexp.MustCompile(`^[A-Za-z0-9_-]{1,64}$`)
func isValidBunnyID(id string) bool { return bunnyRe.MatchString(id) }

var voiceRe = regexp.MustCompile(`^[A-Za-z0-9 _.-]{1,64}$`)
func isValidVoice(v string) bool { return voiceRe.MatchString(v) }

func isValidText(t string) bool {
	if len(t) == 0 || len(t) > 512 { return false }
	// rudimentary control char check
	for i := 0; i < len(t); i++ { if t[i] < 0x20 && t[i] != 0x09 && t[i] != 0x0A && t[i] != 0x0D { return false } }
	return true
}
