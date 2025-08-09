package account

type Manager struct{}

func NewManager() *Manager { return &Manager{} }

// Placeholder for token-based account fetching and permissions
func (m *Manager) HasAccess(token string) bool { return true }
