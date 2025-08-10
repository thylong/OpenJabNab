package ztamp

import "sync"

// Manager tracks known ztamps (RFID tags). For now it only exposes totals.
type Manager struct {
    mu    sync.RWMutex
    known map[string]struct{}
}

func NewManager() *Manager {
    return &Manager{known: make(map[string]struct{})}
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
