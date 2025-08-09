package main

import (
    "fmt"
    "log/slog"
    "time"

    "OpenJabNab/internal/api"
    configpkg "OpenJabNab/internal/config"
    "OpenJabNab/internal/account"
    "OpenJabNab/internal/bunny"
    "OpenJabNab/internal/server/httpbridge"
    "OpenJabNab/internal/server/xmpp"
    "OpenJabNab/internal/stats"
)

type servers struct {
	stop chan struct{}
}

func startServers(logger *slog.Logger, cfg *configpkg.Config) (*servers, error) {
    // Managers
    bunMgr := bunny.NewManager(cfg.MaxNumberOfBunnies)
    accMgr := account.NewManager()
    if cfg.Accounts.Username != "" {
        accMgr.AddUser(cfg.Accounts.Username, cfg.Accounts.Password)
        logger.Info("loaded test account", slog.String("user", cfg.Accounts.Username))
    }
    // Stats provider using config until live data is available
    statProv := stats.NewConfigStats(cfg)

    apiMgr := &api.Manager{Logger: logger, Stats: statProv}
    apiMgr.Plugins = api.DefaultPluginAPI{}
    apiMgr.Bunnies = api.DefaultBunnyAPI{B: bunMgr}
    apiMgr.Ztamps = api.DefaultZtampAPI{}
    apiMgr.Accounts = api.DefaultAccountsAPI{A: accMgr}
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
        xs.OnConnect = func(id string) { if id != "" { bunMgr.Connect(id); logger.Info("bunny connected", slog.String("id", id)) } }
        xs.OnDisconnect = func(id string) { if id != "" { bunMgr.Disconnect(id); logger.Info("bunny disconnected", slog.String("id", id)) } }
        xs.GetPassword = accMgr.GetPassword
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
