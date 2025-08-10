package plugin

import "sync"

type Manager struct {
	mu sync.RWMutex
	list []Plugin
	enabled map[string]bool
}

func NewManager() *Manager { return &Manager{enabled: make(map[string]bool)} }

func (m *Manager) Register(p Plugin) {
	m.mu.Lock(); defer m.mu.Unlock()
	m.list = append(m.list, p)
	m.enabled[p.Name()] = p.Enabled()
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
