package plugin_test

import (
	"testing"

	plugman "OpenJabNab/internal/plugin"
	plugloc "OpenJabNab/internal/plugins/locate"
	cfgpkg "OpenJabNab/internal/config"
)

func TestCannotDisableRequiredOrSystem(t *testing.T) {
	cfg := &cfgpkg.Config{}
	pm := plugman.NewManager()
	pm.Register(plugloc.New(cfg)) // RequiredPlugin
	if ok := pm.Enable("locate", false); ok { t.Fatalf("should not disable required plugin") }
	// enable true should be a no-op success
	if ok := pm.Enable("locate", true); !ok { t.Fatalf("expected enable true to succeed") }
}
