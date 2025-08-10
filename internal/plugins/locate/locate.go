package locate

import (
    "fmt"
    "OpenJabNab/internal/config"
    p "OpenJabNab/internal/plugin"
)

type Plugin struct{
	enabled bool
	cfg *config.Config
}

func New(cfg *config.Config) *Plugin { return &Plugin{enabled: true, cfg: cfg} }

func (pl *Plugin) Name() string { return "locate" }
func (pl *Plugin) Type() p.PluginType { return p.RequiredPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}

func (pl *Plugin) HttpRequestHandle(r *p.Request) bool {
	if len(r.URI) >= len("/vl/locate.jsp") && r.URI[:len("/vl/locate.jsp")] == "/vl/locate.jsp" {
		host := pl.cfg.OpenJabNabServers.XmppServer
		xmppPort := pl.cfg.OpenJabNabServers.ListeningXmppPort
        reply := "ping " + host + "\n" + "broad " + host + "\n" + "xmpp_domain " + host + ":" + fmt.Sprintf("%d", xmppPort) + "\n"
		r.Reply = []byte(reply)
		return true
	}
	return false
}
