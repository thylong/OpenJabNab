package plugin

import (
	"path/filepath"
	"sync"
	"strings"
	ini "gopkg.in/ini.v1"
)

// Settings provides INI-backed per-plugin settings: plugin_<name>.ini
// Not concurrent-safe at the INI file level; guarded by Manager or plugin.
type Settings struct {
	path string
	mu   sync.Mutex
	cfg  *ini.File
}

func NewSettings(dir, name string) (*Settings, error) {
	fname := filepath.Join(dir, "plugin_"+sanitize(name)+".ini")
	cfg, _ := ini.LooseLoad(fname) // create if missing on save
	return &Settings{path: fname, cfg: cfg}, nil
}

func sanitize(s string) string {
	return strings.Map(func(r rune) rune {
		if (r >= 'a' && r <= 'z') || (r >= 'A' && r <= 'Z') || (r >= '0' && r <= '9') || r == '-' || r == '_' { return r }
		return '_' 
	}, s)
}

func (s *Settings) Get(section, key, def string) string {
	s.mu.Lock(); defer s.mu.Unlock()
	sec := s.cfg.Section(section)
	return sec.Key(key).MustString(def)
}

func (s *Settings) Set(section, key, val string) error {
	s.mu.Lock(); defer s.mu.Unlock()
	s.cfg.Section(section).Key(key).SetValue(val)
	return s.cfg.SaveTo(s.path)
}

// Keys returns all keys in a section
func (s *Settings) Keys(section string) []string {
    s.mu.Lock(); defer s.mu.Unlock()
    sec := s.cfg.Section(section)
    ks := sec.Keys()
    out := make([]string, 0, len(ks))
    for _, k := range ks { out = append(out, k.Name()) }
    return out
}

// Delete removes a key from a section and persists the file
func (s *Settings) Delete(section, key string) error {
    s.mu.Lock(); defer s.mu.Unlock()
    sec := s.cfg.Section(section)
    sec.DeleteKey(key)
    return s.cfg.SaveTo(s.path)
}
