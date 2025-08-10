package plugin_test

import (
    "testing"

    plugman "OpenJabNab/internal/plugin"
    plugears "OpenJabNab/internal/plugins/ears"
)

func contains(xs []string, s string) bool {
	for _, x := range xs { if x == s { return true } }
	return false
}

func TestPluginEnableDisablePersistsAcrossManagers(t *testing.T) {
	dir := t.TempDir()

	// First manager: register default-enabled plugin, then disable it
	m1 := plugman.NewManager()
	m1.SetSettingsDir(dir)
    m1.Register(plugears.New())
    if !contains(m1.EnabledNames(), "ears") {
		t.Fatalf("expected logger enabled by default")
	}
	if ok := m1.Enable("ears", false); !ok { t.Fatalf("failed to disable plugin") }

	// Second manager: should load disabled state from disk
	m2 := plugman.NewManager()
	m2.SetSettingsDir(dir)
    m2.Register(plugears.New())
    if contains(m2.EnabledNames(), "ears") {
		t.Fatalf("expected logger disabled after reload")
	}

	// Re-enable and verify a subsequent manager sees it enabled
	if ok := m2.Enable("ears", true); !ok { t.Fatalf("failed to enable plugin in m2") }
	m3 := plugman.NewManager()
	m3.SetSettingsDir(dir)
	m3.Register(plugears.New())
	if !contains(m3.EnabledNames(), "ears") {
		t.Fatalf("expected logger enabled after re-enable and reload")
	}
}
