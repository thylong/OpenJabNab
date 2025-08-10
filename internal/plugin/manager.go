package plugin

import (
    "strconv"
    "sync"
)

type Manager struct {
	mu sync.RWMutex
	list []Plugin
	enabled map[string]bool
    // settingsDir, when set, enables persistence of plugin enabled/disabled state
    settingsDir string
}

func NewManager() *Manager { return &Manager{enabled: make(map[string]bool)} }

// SetSettingsDir configures the directory used to persist plugin state (plugin_<name>.ini)
func (m *Manager) SetSettingsDir(dir string) { m.mu.Lock(); m.settingsDir = dir; m.mu.Unlock() }

func (m *Manager) Register(p Plugin) {
	m.mu.Lock(); defer m.mu.Unlock()
	m.list = append(m.list, p)
    // default to the plugin's internal enabled state
    name := p.Name()
    m.enabled[name] = p.Enabled()
    // load persisted enabled flag if available, otherwise persist current state
    if m.settingsDir != "" {
        if st, err := NewSettings(m.settingsDir, name); err == nil {
            if v := st.Get("plugin", "enabled", ""); v != "" {
                if b, err := strconv.ParseBool(v); err == nil {
                    p.SetEnabled(b)
                    m.enabled[name] = b
                }
            } else {
                // persist current state
                _ = st.Set("plugin", "enabled", strconv.FormatBool(p.Enabled()))
            }
        }
    }
}

func (m *Manager) Names() []string {
    m.mu.RLock(); defer m.mu.RUnlock()
    out := make([]string, 0, len(m.list))
    for _, p := range m.list { out = append(out, p.Name()) }
    return out
}

func (m *Manager) EnabledNames() []string {
    m.mu.RLock(); defer m.mu.RUnlock()
    out := make([]string, 0, len(m.list))
    for _, p := range m.list { if m.enabled[p.Name()] { out = append(out, p.Name()) } }
    return out
}

func (m *Manager) Enable(name string, on bool) bool {
    m.mu.Lock(); defer m.mu.Unlock()
    for _, p := range m.list {
        if p.Name() == name {
            p.SetEnabled(on)
            m.enabled[name] = on
            // persist change if configured
            if m.settingsDir != "" {
                if st, err := NewSettings(m.settingsDir, name); err == nil {
                    _ = st.Set("plugin", "enabled", strconv.FormatBool(on))
                }
            }
            return true
        }
    }
    return false
}

func (m *Manager) HttpRequest(req *Request) bool {
	m.mu.RLock(); defer m.mu.RUnlock()
	for _, p := range m.list {
		if !m.enabled[p.Name()] { continue }
		p.HttpRequestBefore(req)
	}
	for _, p := range m.list {
		if !m.enabled[p.Name()] { continue }
		if p.HttpRequestHandle(req) { return true }
	}
	for _, p := range m.list {
		if !m.enabled[p.Name()] { continue }
		p.HttpRequestAfter(req)
	}
	return false
}

// ProcessPluginApi routes /ojn_api/plugin/<name>/<function>
func (m *Manager) ProcessPluginApi(name, function string, get map[string]string) (bool, []byte, error) {
    m.mu.RLock(); defer m.mu.RUnlock()
    for _, p := range m.list {
        if p.Name() != name || !m.enabled[p.Name()] { continue }
        if ah, ok := p.(ApiHandler); ok {
            handled, xml, err := ah.ProcessPluginApi(function, get)
            if handled { return true, xml, err }
        }
    }
    return false, nil, nil
}

func (m *Manager) OnButton(id string, clicks int) {
    m.mu.RLock(); defer m.mu.RUnlock()
    for _, p := range m.list {
        if !m.enabled[p.Name()] { continue }
        if h, ok := p.(ButtonHandler); ok { h.OnButton(id, clicks) }
    }
}

func (m *Manager) OnEars(id string, left, right int) {
    m.mu.RLock(); defer m.mu.RUnlock()
    for _, p := range m.list {
        if !m.enabled[p.Name()] { continue }
        if h, ok := p.(EarsHandler); ok { h.OnEars(id, left, right) }
    }
}
