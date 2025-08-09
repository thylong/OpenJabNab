package main

import (
    "fmt"
    "log/slog"
    "time"

    "OpenJabNab/internal/api"
    configpkg "OpenJabNab/internal/config"
    "OpenJabNab/internal/server/httpbridge"
    "OpenJabNab/internal/server/xmpp"
    "OpenJabNab/internal/stats"
)

type servers struct {
	stop chan struct{}
}

func startServers(logger *slog.Logger, cfg *configpkg.Config) (*servers, error) {
    // Use a basic stats provider; replace with real managers as they are ported
    statProv := stats.NewConfigStats(cfg)
    apiMgr := &api.Manager{Logger: logger, Stats: statProv}
    stop := make(chan struct{})

    if cfg.HttpListener {
        addr := fmt.Sprintf("127.0.0.1:%d", cfg.OpenJabNabServers.ListeningHttpPort)
        hb := httpbridge.New(addr, logger, apiMgr)
        go func() { _ = hb.ListenAndServe(stop) }()
        logger.Info("httpbridge listening", slog.String("addr", addr))
    } else {
        logger.Warn("HTTP listener disabled by config")
    }

    if cfg.XmppListener {
        xaddr := fmt.Sprintf("0.0.0.0:%d", cfg.OpenJabNabServers.ListeningXmppPort)
        xs := &xmpp.Server{Addr: xaddr, Domain: cfg.OpenJabNabServers.XmppServer, Logger: logger}
        go func() { _ = xs.ListenAndServe(stop) }()
        logger.Info("xmpp listening", slog.String("addr", xaddr), slog.String("domain", cfg.OpenJabNabServers.XmppServer))
    } else {
        logger.Warn("XMPP listener disabled by config")
    }

    return &servers{stop: stop}, nil
}

func (s *servers) shutdown(logger *slog.Logger) {
	close(s.stop)
	time.Sleep(200 * time.Millisecond)
}
