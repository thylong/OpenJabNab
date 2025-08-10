package logger

import (
	"log/slog"
	p "OpenJabNab/internal/plugin"
)

type Plugin struct{
	enabled bool
	log *slog.Logger
}

func New(l *slog.Logger) *Plugin { return &Plugin{enabled: true, log: l} }

func (pl *Plugin) Name() string { return "logger" }
func (pl *Plugin) Type() p.PluginType { return p.SystemPlugin }
func (pl *Plugin) Enabled() bool { return pl.enabled }
func (pl *Plugin) SetEnabled(b bool) { pl.enabled = b }

func (pl *Plugin) HttpRequestBefore(r *p.Request) {}
func (pl *Plugin) HttpRequestAfter(r *p.Request) {}
func (pl *Plugin) HttpRequestHandle(r *p.Request) bool { return false }

func (pl *Plugin) OnButton(id string, clicks int) { pl.log.Info("plugin: button", slog.String("id", id), slog.Int("clicks", clicks)) }
func (pl *Plugin) OnEars(id string, left, right int) { pl.log.Info("plugin: ears", slog.String("id", id), slog.Int("left", left), slog.Int("right", right)) }
