package api

import (
    "encoding/xml"
    "log/slog"
    "strings"
)

type Manager struct {
	Logger *slog.Logger
	// Inject services/managers here as needed
	Stats StatsProvider
}

type StatsProvider interface {
	BunnyTotals() (total int, connected int)
	ZtampTotal() int
	PluginTotals() (total int, enabled int)
}

type apiEnvelope struct {
    XMLName xml.Name `xml:"api"`
    Inner   []byte   `xml:",innerxml"`
}

type violetEnvelope struct {
    XMLName xml.Name `xml:"rsp"`
    Inner   []byte   `xml:",innerxml"`
}

// Process routes API calls based on the parsed URI and query parameters.
func (m *Manager) Process(rawURI string, uri string, get map[string]string) (contentType string, data []byte) {
    // Legacy routes: /ojn_api/..., /ojn/FR/api...
    if strings.HasPrefix(uri, "/ojn/FR/api") {
        return "text/xml; charset=utf-8", m.violetApi(rawURI, get)
    }
    if strings.HasPrefix(uri, "/ojn_api/") {
        return "text/xml; charset=utf-8", m.stdApi(strings.TrimPrefix(uri, "/ojn_api/"), get)
    }
    // Unknown path for now; return simple text
    return "text/plain", []byte("404 Not Found")
}

func (m *Manager) stdApi(path string, get map[string]string) []byte {
	switch path {
	case "global/about":
		return m.wrapAPI([]byte(`<value>OpenJabNab Go - bootstrap</value>`))
	case "global/ping":
		t, c := 0, 0
		if m.Stats != nil { t, c = m.Stats.BunnyTotals() }
		return m.wrapAPI([]byte(`<value>` + itoa(c) + `/` + itoa(t) + `/` + itoa(t) + `</value>`))
	case "global/stats":
		bt, bc := 0, 0
		zt := 0
		pt, pe := 0, 0
		if m.Stats != nil {
			bt, bc = m.Stats.BunnyTotals()
			zt = m.Stats.ZtampTotal()
			pt, pe = m.Stats.PluginTotals()
		}
		inner := `<bunnies>` + itoa(bt) + `</bunnies>` +
			`<connected_bunnies>` + itoa(bc) + `</connected_bunnies>` +
			`<ztamps>` + itoa(zt) + `</ztamps>` +
			`<plugins>` + itoa(pt) + `</plugins>` +
			`<enabled_plugins>` + itoa(pe) + `</enabled_plugins>`
		return m.wrapAPI([]byte(inner))
	default:
		return m.wrapAPI([]byte(`<error>Unknown Api Call</error>`))
	}
}

func (m *Manager) violetApi(rawURI string, get map[string]string) []byte {
	// Minimal stub; device-specific format
	return m.wrapViolet([]byte(`<message>PONG</message><comment></comment>`))
}

func (m *Manager) wrapAPI(inner []byte) []byte {
	env := apiEnvelope{Inner: inner}
	b, _ := xml.Marshal(env)
	return b
}

func (m *Manager) wrapViolet(inner []byte) []byte {
	env := violetEnvelope{Inner: inner}
	b, _ := xml.Marshal(env)
	return b
}

func itoa(i int) string { return strconvItoa(i) }
