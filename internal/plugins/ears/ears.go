package ears

import (
    p "OpenJabNab/internal/plugin"
    "sync"
)

type Plugin struct {
    enabled bool
    mu      sync.RWMutex
    // pos stores last-known ears positions per bunny id: [left,right]
    pos     map[string][2]int
}

func New() *Plugin { return &Plugin{enabled: true, pos: make(map[string][2]int)} }

func (pl *Plugin) Name() string { return "ears" }
func (pl *Plugin) Type() p.PluginType { return p.BunnyPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}
func (pl *Plugin) HttpRequestHandle(r *p.Request) bool { return false }

// OnEars records the latest ears position for a bunny
func (pl *Plugin) OnEars(bunnyID string, left, right int) {
    pl.mu.Lock();
    pl.pos[bunnyID] = [2]int{left, right}
    pl.mu.Unlock()
}

// ProcessPluginApi supports:
// - get?bunny=<id>  → returns <left>..</left><right>..</right>
func (pl *Plugin) ProcessPluginApi(function string, get map[string]string) (bool, []byte, error) {
    switch function {
    case "get":
        id := get["bunny"]
        if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        pl.mu.RLock(); v, ok := pl.pos[id]; pl.mu.RUnlock()
        if !ok { return true, []byte(`<left/><right/>`), nil }
        xml := []byte(`<left>` + itoa(v[0]) + `</left><right>` + itoa(v[1]) + `</right>`)
        return true, xml, nil
    }
    return false, nil, nil
}

// local thin wrapper; implementation is in package-local itoa_compat files elsewhere
func itoa(i int) string { return strconvItoa(i) }
