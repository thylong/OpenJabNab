package account

import (
    "crypto/rand"
    "encoding/hex"
    "sync"
    "time"
)

type Manager struct{
    mu sync.RWMutex
    users map[string]string // username -> password
    sessions map[string]session // token -> session
}

type session struct {
    Username string
    Expires  time.Time
}

func NewManager() *Manager { return &Manager{users: make(map[string]string), sessions: make(map[string]session)} }

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

func (m *Manager) GetPassword(username string) (string, bool) {
    m.mu.RLock(); defer m.mu.RUnlock()
    pw, ok := m.users[username]
    return pw, ok
}

// IssueToken validates credentials and creates a short-lived token (24h) on success.
func (m *Manager) IssueToken(username, password string) (string, bool) {
    if !m.Validate(username, password) { return "", false }
    b := make([]byte, 16)
    _, _ = rand.Read(b)
    token := hex.EncodeToString(b)
    m.mu.Lock()
    m.sessions[token] = session{Username: username, Expires: time.Now().Add(24 * time.Hour)}
    m.mu.Unlock()
    return token, true
}

func (m *Manager) ValidateToken(token string) bool {
    m.mu.RLock()
    s, ok := m.sessions[token]
    m.mu.RUnlock()
    if !ok { return false }
    if time.Now().After(s.Expires) {
        m.RevokeToken(token)
        return false
    }
    return true
}

func (m *Manager) RevokeToken(token string) {
    m.mu.Lock(); defer m.mu.Unlock()
    delete(m.sessions, token)
}
