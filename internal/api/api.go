package api

import (
    "encoding/xml"
    "log/slog"
    "strings"
    psettings "OpenJabNab/internal/plugin"
)

type Manager struct {
	Logger *slog.Logger
	// Inject services/managers here as needed
	Stats StatsProvider

    Plugins  PluginAPI
    Bunnies  BunnyAPI
    Ztamps   ZtampAPI
    Accounts AccountsAPI

    // Optional: direct access to plugin manager for parity endpoints
    PluginNames func() []string
    EnabledPluginNames func() []string
    SetPluginEnabled func(name string, on bool) bool
    PluginProcess func(name, function string, get map[string]string) (bool, []byte, error)

    // PluginsDir for settings files
    PluginsDir string
}

type StatsProvider interface {
	BunnyTotals() (total int, connected int)
	ZtampTotal() int
	PluginTotals() (total int, enabled int)
}

// Subsystem APIs (stubs for now). They should return an XML fragment already formatted
// for inclusion into the api envelope, or an error which will be wrapped as <error>.
type PluginAPI interface {
    Process(accountToken string, request string, get map[string]string) (xmlFragment []byte, err error)
}

type BunnyAPI interface {
    Process(accountToken string, request string, get map[string]string) (xmlFragment []byte, err error)
    ProcessViolet(request string, get map[string]string) (xmlFragment []byte, err error)
}

type ZtampAPI interface {
    Process(accountToken string, request string, get map[string]string) (xmlFragment []byte, err error)
}

type AccountsAPI interface {
    Process(accountToken string, request string, get map[string]string) (xmlFragment []byte, err error)
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
        // Route by subnamespace
        sub := strings.TrimPrefix(uri, "/ojn_api/")
        // Extract token if present
        token := get["token"]
        switch {
        case strings.HasPrefix(sub, "global/"):
            return "text/xml; charset=utf-8", m.stdApi(sub, get)
        case strings.HasPrefix(sub, "plugins/"):
            if m.Plugins == nil {
                return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Plugins API not implemented"))
            }
            frag, err := m.Plugins.Process(token, strings.TrimPrefix(sub, "plugins/"), get)
            if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
            return "text/xml; charset=utf-8", m.wrapAPI(frag)
        case strings.HasPrefix(sub, "plugins-list"):
            if m.PluginNames != nil {
                names := m.PluginNames()
                inner := "<list>"
                for _, n := range names { inner += "<item>" + n + "</item>" }
                inner += "</list>"
                return "text/xml; charset=utf-8", m.wrapAPI([]byte(inner))
            }
            return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("No plugin manager"))
        case strings.HasPrefix(sub, "plugins-enabled"):
            if m.EnabledPluginNames != nil {
                names := m.EnabledPluginNames()
                inner := "<list>"
                for _, n := range names { inner += "<item>" + n + "</item>" }
                inner += "</list>"
                return "text/xml; charset=utf-8", m.wrapAPI([]byte(inner))
            }
            return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("No plugin manager"))
        case strings.HasPrefix(sub, "plugin-enable/"):
            if m.SetPluginEnabled == nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("No plugin manager")) }
            name := strings.TrimPrefix(sub, "plugin-enable/")
            if m.SetPluginEnabled(name, true) { return "text/xml; charset=utf-8", m.wrapAPI([]byte(`<ok/>`)) }
            return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Unknown plugin"))
        case strings.HasPrefix(sub, "plugin-disable/"):
            if m.SetPluginEnabled == nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("No plugin manager")) }
            name := strings.TrimPrefix(sub, "plugin-disable/")
            if m.SetPluginEnabled(name, false) { return "text/xml; charset=utf-8", m.wrapAPI([]byte(`<ok/>`)) }
            return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Unknown plugin"))
        case strings.HasPrefix(sub, "plugin/"):
            // legacy: plugin/<name>/<function>
            parts := strings.Split(strings.TrimPrefix(sub, "plugin/"), "/")
            if len(parts) != 2 { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Malformed Plugin Api Call")) }
            name, function := parts[0], parts[1]
            // Settings helpers
            if function == "getsetting" {
                key := get["key"]
                if key == "" { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Missing key")) }
                st, err := pluginNewSettings(m.PluginsDir, name)
                if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
                val := st.Get("plugin", key, "")
                return "text/xml; charset=utf-8", m.wrapAPI([]byte(`<value>` + val + `</value>`))
            }
            if function == "setsetting" {
                key := get["key"]
                val := get["value"]
                if key == "" { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Missing key")) }
                st, err := pluginNewSettings(m.PluginsDir, name)
                if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
                if err := st.Set("plugin", key, val); err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
                return "text/xml; charset=utf-8", m.wrapAPI([]byte(`<ok/>`))
            }
            if function == "listsettings" {
                st, err := pluginNewSettings(m.PluginsDir, name)
                if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
                keys := st.Keys("plugin")
                inner := "<list>"
                for _, k := range keys { inner += "<item>" + k + "</item>" }
                inner += "</list>"
                return "text/xml; charset=utf-8", m.wrapAPI([]byte(inner))
            }
            if function == "delsetting" {
                key := get["key"]
                if key == "" { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Missing key")) }
                st, err := pluginNewSettings(m.PluginsDir, name)
                if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
                if err := st.Delete("plugin", key); err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
                return "text/xml; charset=utf-8", m.wrapAPI([]byte(`<ok/>`))
            }
            if m.PluginProcess == nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Plugin API not implemented")) }
            if handled, frag, err := m.PluginProcess(name, function, get); handled {
                if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
                return "text/xml; charset=utf-8", m.wrapAPI(frag)
            }
            return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Unknown Plugin or function"))
        case strings.HasPrefix(sub, "bunnies/"):
            if m.Bunnies == nil {
                return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Bunnies API not implemented"))
            }
            frag, err := m.Bunnies.Process(token, strings.TrimPrefix(sub, "bunnies/"), get)
            if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
            return "text/xml; charset=utf-8", m.wrapAPI(frag)
        case strings.HasPrefix(sub, "bunny/"):
            if m.Bunnies == nil {
                return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Bunny API not implemented"))
            }
            frag, err := m.Bunnies.Process(token, strings.TrimPrefix(sub, "bunny/"), get)
            if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
            return "text/xml; charset=utf-8", m.wrapAPI(frag)
        case strings.HasPrefix(sub, "ztamps/"):
            if m.Ztamps == nil {
                return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Ztamps API not implemented"))
            }
            frag, err := m.Ztamps.Process(token, strings.TrimPrefix(sub, "ztamps/"), get)
            if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
            return "text/xml; charset=utf-8", m.wrapAPI(frag)
        case strings.HasPrefix(sub, "ztamp/"):
            if m.Ztamps == nil {
                return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Ztamp API not implemented"))
            }
            frag, err := m.Ztamps.Process(token, strings.TrimPrefix(sub, "ztamp/"), get)
            if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
            return "text/xml; charset=utf-8", m.wrapAPI(frag)
        case strings.HasPrefix(sub, "accounts/"):
            if m.Accounts == nil {
                return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Accounts API not implemented"))
            }
            frag, err := m.Accounts.Process(token, strings.TrimPrefix(sub, "accounts/"), get)
            if err != nil { return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment(err.Error())) }
            return "text/xml; charset=utf-8", m.wrapAPI(frag)
        default:
            return "text/xml; charset=utf-8", m.wrapAPI(m.errFragment("Unknown Api Call"))
        }
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

func (m *Manager) errFragment(msg string) []byte {
    // Minimal sanitization; full CDATA handling can be added later
    return []byte(`<error>` + msg + `</error>`)
}

// thin wrapper to avoid importing Settings type in tests outside this package
func pluginNewSettings(dir, name string) (*psettings.Settings, error) { return psettings.NewSettings(dir, name) }
