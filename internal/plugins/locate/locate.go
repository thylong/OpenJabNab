package locate

import (
    "fmt"
    "net/url"
    "strings"
    cfgpkg "OpenJabNab/internal/config"
    p "OpenJabNab/internal/plugin"
)

type Plugin struct{
	enabled bool
    cfg *cfgpkg.Config
    settings *p.Settings
}

func New(cfg *cfgpkg.Config) *Plugin {
    st, _ := p.NewSettings(cfg.PluginsDir, "locate")
    return &Plugin{enabled: true, cfg: cfg, settings: st}
}

func (pl *Plugin) Name() string { return "locate" }
func (pl *Plugin) VisualName() string { return "Locate" }
func (pl *Plugin) Type() p.PluginType { return p.RequiredPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}

func (pl *Plugin) HttpRequestHandle(r *p.Request) bool {
    if len(r.URI) >= len("/vl/locate.jsp") && r.URI[:len("/vl/locate.jsp")] == "/vl/locate.jsp" {
        // Resolve XMPP host and Broad host separately to match device expectations
        xmppHost := pl.settings.Get("locate", "XmppServer", pl.cfg.OpenJabNabServers.XmppServer)
        xmppPortStr := pl.settings.Get("locate", "ListeningXmppPort", fmt.Sprintf("%d", pl.cfg.OpenJabNabServers.ListeningXmppPort))
        broad := pl.settings.Get("locate", "BroadServer", pl.cfg.OpenJabNabServers.BroadServer)
        broadHost := broad
        if broadHost == "" { broadHost = xmppHost }
        // If BroadServer includes scheme/path, extract host
        if strings.HasPrefix(broadHost, "http://") || strings.HasPrefix(broadHost, "https://") {
            if u, err := url.Parse(broadHost); err == nil && u.Host != "" { broadHost = u.Host }
        }
        reply := "ping " + xmppHost + "\n" + "broad " + broadHost + "\n" + "xmpp_domain " + xmppHost + ":" + xmppPortStr + "\n"
		r.Reply = []byte(reply)
		return true
	}
	return false
}
