package ztamp

import "sync"

// Manager tracks known ztamps (RFID tags). For now it only exposes totals.
type Manager struct {
    mu    sync.RWMutex
    known map[string]struct{}
    assigned map[string]string // ztampID -> bunnyID
}

func NewManager() *Manager {
    return &Manager{known: make(map[string]struct{}), assigned: make(map[string]string)}
}

func (m *Manager) Register(id string) {
    m.mu.Lock(); defer m.mu.Unlock()
    m.known[id] = struct{}{}
}

func (m *Manager) Unregister(id string) {
    m.mu.Lock(); defer m.mu.Unlock()
    delete(m.known, id)
}

func (m *Manager) Count() int {
    m.mu.RLock(); defer m.mu.RUnlock()
    return len(m.known)
}

func (m *Manager) List() []string {
    m.mu.RLock(); defer m.mu.RUnlock()
    out := make([]string, 0, len(m.known))
    for id := range m.known { out = append(out, id) }
    return out
}

func (m *Manager) IsKnown(id string) bool {
    m.mu.RLock(); defer m.mu.RUnlock()
    _, ok := m.known[id]
    return ok
}

func (m *Manager) Assign(id, bunny string) bool {
    m.mu.Lock(); defer m.mu.Unlock()
    if _, ok := m.known[id]; !ok { return false }
    m.assigned[id] = bunny
    return true
}

func (m *Manager) Unassign(id string) {
    m.mu.Lock(); defer m.mu.Unlock()
    delete(m.assigned, id)
}

func (m *Manager) AssignedTo(id string) (string, bool) {
    m.mu.RLock(); defer m.mu.RUnlock()
    b, ok := m.assigned[id]
    return b, ok
}

// Aliases for API naming
func (m *Manager) Add(id string) { m.Register(id) }
func (m *Manager) Remove(id string) { m.Unregister(id) }
