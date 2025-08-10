package bunny

import (
    "path/filepath"
    ini "gopkg.in/ini.v1"
    "sync"
)

type Manager struct {
	mu        sync.Mutex
    connected map[string]struct{}
    names     map[string]string
	capacity  int
    statePath string
}

func NewManager(capacity int) *Manager {
    return &Manager{connected: make(map[string]struct{}), names: make(map[string]string), capacity: capacity}
}

func (m *Manager) Connect(id string) {
	m.mu.Lock()
	defer m.mu.Unlock()
	m.connected[id] = struct{}{}
    if _, ok := m.names[id]; !ok {
        m.names[id] = ""
    }
}

func (m *Manager) Disconnect(id string) {
	m.mu.Lock()
	defer m.mu.Unlock()
	delete(m.connected, id)
}

func (m *Manager) ConnectedCount() int {
	m.mu.Lock(); defer m.mu.Unlock()
	return len(m.connected)
}

func (m *Manager) Capacity() int { return m.capacity }

func (m *Manager) ListConnected() []string {
    m.mu.Lock(); defer m.mu.Unlock()
    out := make([]string, 0, len(m.connected))
    for id := range m.connected { out = append(out, id) }
    return out
}

func (m *Manager) IsConnected(id string) bool {
    m.mu.Lock(); defer m.mu.Unlock()
    _, ok := m.connected[id]
    return ok
}

func (m *Manager) SetName(id, name string) {
    m.mu.Lock(); defer m.mu.Unlock()
    m.names[id] = name
    if m.statePath != "" {
        cfg, _ := ini.LooseLoad(m.statePath)
        cfg.Section("bunnies").Key(id).SetValue(name)
        _ = cfg.SaveTo(m.statePath)
    }
}

func (m *Manager) GetName(id string) string {
    m.mu.Lock(); defer m.mu.Unlock()
    return m.names[id]
}

// Persistence (simple INI on disk)
func (m *Manager) LoadState(dir string) error {
    m.mu.Lock(); defer m.mu.Unlock()
    m.statePath = filepath.Join(dir, "bunnies.ini")
    cfg, err := ini.LooseLoad(m.statePath)
    if err != nil { return err }
    sect := cfg.Section("bunnies")
    for _, k := range sect.Keys() {
        m.names[k.Name()] = k.Value()
    }
    return nil
}
