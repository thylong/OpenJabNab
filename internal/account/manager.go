package account

import "sync"

type Manager struct{
    mu sync.RWMutex
    users map[string]string // username -> password
}

func NewManager() *Manager { return &Manager{users: make(map[string]string)} }

// Placeholder for token-based account fetching and permissions
func (m *Manager) HasAccess(token string) bool { return true }

func (m *Manager) AddUser(username, password string) {
    m.mu.Lock(); defer m.mu.Unlock()
    m.users[username] = password
}

func (m *Manager) Validate(username, password string) bool {
    m.mu.RLock(); defer m.mu.RUnlock()
    if pw, ok := m.users[username]; ok {
        return pw == password
    }
    return false
}
