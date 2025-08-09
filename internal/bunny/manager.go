package bunny

import "sync"

type Manager struct {
	mu        sync.Mutex
	connected map[string]struct{}
	capacity  int
}

func NewManager(capacity int) *Manager {
	return &Manager{connected: make(map[string]struct{}), capacity: capacity}
}

func (m *Manager) Connect(id string) {
	m.mu.Lock()
	defer m.mu.Unlock()
	m.connected[id] = struct{}{}
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
