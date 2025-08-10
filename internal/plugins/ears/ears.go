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
    send    func(bunnyID string, payload []byte) bool
}

func New() *Plugin { return &Plugin{enabled: true, pos: make(map[string][2]int)} }

func (pl *Plugin) Name() string { return "ears" }
func (pl *Plugin) VisualName() string { return "Ears" }
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
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        pl.mu.RLock(); v, ok := pl.pos[id]; pl.mu.RUnlock()
        if !ok { return true, []byte(`<left/><right/>`), nil }
        xml := []byte(`<left>` + itoa(v[0]) + `</left><right>` + itoa(v[1]) + `</right>`)
        return true, xml, nil
    case "set":
        id := get["bunny"]; if id == "" { id = get["id"] }
        if id == "" { return true, []byte(`<error>Missing bunny</error>`), nil }
        if !isValidBunnyID(id) { return true, []byte(`<error>Invalid bunny</error>`), nil }
        // accept left/right in [0..15] similar to original behavior
        leftS, rightS := get["left"], get["right"]
        if leftS == "" || rightS == "" { return true, []byte(`<error>Missing left/right</error>`), nil }
        // build ambient packet: 0x7F 0xFF 0xFF 0xFE, then pairs (service,value): 0x03 left, 0x04 right
        // According to legacy, MoveLeftEar=3, MoveRightEar=4
        pkt := []byte{0x7F, 0xFF, 0xFF, 0xFE}
        // naive parse ints
        li := atoi(leftS); if li < 0 { li = 0 }; if li > 15 { li = 15 }
        ri := atoi(rightS); if ri < 0 { ri = 0 }; if ri > 15 { ri = 15 }
        pkt = append(pkt, 0x03, byte(li), 0x04, byte(ri))
        if pl.send != nil && pl.send(id, pkt) {
            // update cached pos
            pl.mu.Lock(); pl.pos[id] = [2]int{li, ri}; pl.mu.Unlock()
            return true, []byte(`<ok/>`), nil
        }
        return true, []byte(`<error>Not connected</error>`), nil
    }
    return false, nil, nil
}

// local thin wrapper; implementation is in package-local itoa_compat files elsewhere
func itoa(i int) string { return strconvItoa(i) }

func atoi(s string) int {
    n := 0
    for i := 0; i < len(s); i++ { c := s[i]; if c < '0' || c > '9' { break }; n = n*10 + int(c-'0') }
    return n
}

// Implement PacketSenderAware
func (pl *Plugin) SetPacketSender(fn func(bunnyID string, payload []byte) bool) { pl.send = fn }

// basic validation helpers
func isValidBunnyID(id string) bool {
    if len(id) == 0 || len(id) > 64 { return false }
    for i := 0; i < len(id); i++ {
        c := id[i]
        if (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || (c >= '0' && c <= '9') || c=='-' || c=='_' { continue }
        return false
    }
    return true
}
