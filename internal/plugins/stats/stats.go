package stats

import (
    p "OpenJabNab/internal/plugin"
)

type Provider interface{
	BunnyTotals() (int, int)
	ZtampTotal() int
	PluginTotals() (int, int)
}

type Plugin struct{
	enabled bool
	prov Provider
}

func New(prov Provider) *Plugin { return &Plugin{enabled: true, prov: prov} }

func (pl *Plugin) Name() string { return "stats" }
func (pl *Plugin) VisualName() string { return "Stats" }
func (pl *Plugin) Type() p.PluginType { return p.SystemPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}
func (pl *Plugin) HttpRequestHandle(r *p.Request) bool { return false }

func (pl *Plugin) ProcessPluginApi(function string, get map[string]string) (bool, []byte, error) {
	if function == "get" {
		bt, bc := pl.prov.BunnyTotals()
		zt := pl.prov.ZtampTotal()
		pt, pe := pl.prov.PluginTotals()
		xml := []byte(`<bunnies>` + itoa(bt) + `</bunnies>` +
			`<connected_bunnies>` + itoa(bc) + `</connected_bunnies>` +
			`<ztamps>` + itoa(zt) + `</ztamps>` +
			`<plugins>` + itoa(pt) + `</plugins>` +
			`<enabled_plugins>` + itoa(pe) + `</enabled_plugins>`)
		return true, xml, nil
	}
	return false, nil, nil
}

func itoa(i int) string { return strconvItoa(i) }
