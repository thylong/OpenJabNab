package auth

import (
    "regexp"
    "strings"

    cfgpkg "OpenJabNab/internal/config"
    p "OpenJabNab/internal/plugin"
)

type Plugin struct{
    enabled  bool
    settings *p.Settings
}

func New(cfg *cfgpkg.Config) *Plugin {
    st, _ := p.NewSettings(cfg.PluginsDir, "auth")
    return &Plugin{enabled: true, settings: st}
}

func (pl *Plugin) Name() string { return "auth" }
func (pl *Plugin) Type() p.PluginType { return p.RequiredPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}
func (pl *Plugin) HttpRequestHandle(r *p.Request) bool { return false }

// ProcessPluginApi implements parity: setAuthMethod(name), getListOfAuthMethods(), getAuthMethod()
func (pl *Plugin) ProcessPluginApi(function string, get map[string]string) (bool, []byte, error) {
    fn := strings.ToLower(function)
    switch fn {
    case "getlistofauthmethods", "getlistofauths", "list":
        // Parity list: support both DIGEST-MD5 and PLAIN; order mirrors legacy
        inner := "<list><item>DIGEST-MD5</item><item>PLAIN</item><item>BOTH</item></list>"
        return true, []byte(inner), nil
    case "setauthmethod", "set":
        name := strings.ToUpper(strings.TrimSpace(get["name"]))
        if !isValidMethod(name) { return true, []byte(`<error>Unknown method</error>`), nil }
        switch name {
        case "PLAIN", "DIGEST-MD5", "BOTH":
            _ = pl.settings.Set("auth", "method", name)
            return true, []byte(`<ok/>`), nil
        default:
            return true, []byte(`<error>Unknown method</error>`), nil
        }
    case "getauthmethod", "get":
        m := pl.settings.Get("auth", "method", "BOTH")
        return true, []byte(`<method>` + xmlEscape(m) + `</method>`), nil
    }
    return false, nil, nil
}

func xmlEscape(s string) string {
    r := strings.NewReplacer(
        "&", "&amp;",
        "<", "&lt;",
        ">", "&gt;",
        "\"", "&quot;",
        "'", "&apos;",
    )
    return r.Replace(s)
}

var methodRe = regexp.MustCompile(`^(PLAIN|DIGEST-MD5|BOTH)$`)
func isValidMethod(m string) bool { return methodRe.MatchString(m) }
