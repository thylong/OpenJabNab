package ztamp

import (
    "path/filepath"
    ini "gopkg.in/ini.v1"
    "sync"
)

// Manager tracks known ztamps (RFID tags). For now it only exposes totals.
type Manager struct {
    mu    sync.RWMutex
    known map[string]struct{}
    assigned map[string]string // ztampID -> bunnyID
    statePath string
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

// Persistence
func (m *Manager) LoadState(dir string) error {
    m.mu.Lock(); defer m.mu.Unlock()
    m.statePath = filepath.Join(dir, "ztamps.ini")
    cfg, err := ini.LooseLoad(m.statePath)
    if err != nil { return err }
    // known
    for _, k := range cfg.Section("known").Keys() {
        m.known[k.Name()] = struct{}{}
    }
    // assigned
    for _, k := range cfg.Section("assigned").Keys() {
        m.assigned[k.Name()] = k.Value()
    }
    return nil
}

func (m *Manager) save() {
    if m.statePath == "" { return }
    cfg, _ := ini.LooseLoad(m.statePath)
    known := cfg.Section("known"); known.DeleteKey("")
    for id := range m.known { known.Key(id).SetValue("1") }
    assigned := cfg.Section("assigned"); assigned.DeleteKey("")
    for id, b := range m.assigned { assigned.Key(id).SetValue(b) }
    _ = cfg.SaveTo(m.statePath)
}

// Override mutators to persist
// Override mutators to persist (rename internals to avoid redeclare)
func (m *Manager) registerPersist(id string) { m.known[id]=struct{}{}; m.save() }
func (m *Manager) unregisterPersist(id string) { delete(m.known,id); delete(m.assigned,id); m.save() }
func (m *Manager) assignPersist(id, bunny string) bool { if _,ok:=m.known[id];!ok{return false}; m.assigned[id]=bunny; m.save(); return true }
func (m *Manager) unassignPersist(id string) { delete(m.assigned,id); m.save() }
