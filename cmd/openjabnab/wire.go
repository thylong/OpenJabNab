package main

import (
    "fmt"
    "log/slog"
    "time"

    "OpenJabNab/internal/api"
    configpkg "OpenJabNab/internal/config"
    "OpenJabNab/internal/account"
    "OpenJabNab/internal/bunny"
    "OpenJabNab/internal/ztamp"
    "OpenJabNab/internal/server/httpbridge"
    "OpenJabNab/internal/server/xmpp"
    nhttp "OpenJabNab/internal/server/nhttp"
    "OpenJabNab/internal/stats"
    "OpenJabNab/internal/netdump"
    pman "OpenJabNab/internal/plugin"
    locate "OpenJabNab/internal/plugins/locate"
    pluglog "OpenJabNab/internal/plugins/logger"
    plugstats "OpenJabNab/internal/plugins/stats"
    plugears "OpenJabNab/internal/plugins/ears"
    plugradio "OpenJabNab/internal/plugins/webradio"
    plugrecord "OpenJabNab/internal/plugins/record"
    plugauth "OpenJabNab/internal/plugins/auth"
)

type servers struct {
	stop chan struct{}
}

func startServers(logger *slog.Logger, cfg *configpkg.Config) (*servers, error) {
    // Managers
    bunMgr := bunny.NewManager(cfg.MaxNumberOfBunnies)
    _ = bunMgr.LoadState(cfg.StateDir)
    accMgr := account.NewManager()
    if cfg.Accounts.Username != "" {
        accMgr.AddUser(cfg.Accounts.Username, cfg.Accounts.Password)
        logger.Info("loaded test account", slog.String("user", cfg.Accounts.Username))
    }
    // Live stats from managers
    ztMgr := ztamp.NewManager()
    _ = ztMgr.LoadState(cfg.StateDir)
    statProv := stats.NewLive(cfg, bunMgr, ztMgr)

    apiMgr := &api.Manager{Logger: logger, Stats: statProv}
    apiMgr.PluginsDir = cfg.PluginsDir
    apiMgr.Plugins = api.DefaultPluginAPI{}
    // Wire send packet through XMPP server once it's started; create a placeholder and update after xs constructed
    var sendFunc func(id string, payload []byte) bool
    apiMgr.Bunnies = api.DefaultBunnyAPI{B: bunMgr, Send: func(id string, p []byte) bool { if sendFunc != nil { return sendFunc(id, p) }; return false }}
    apiMgr.Ztamps = api.DefaultZtampAPI{ZCount: ztMgr.Count, List: ztMgr.List, Add: ztMgr.Add, Remove: ztMgr.Remove, Assign: ztMgr.Assign, Unassign: ztMgr.Unassign, AssignedTo: ztMgr.AssignedTo}
    apiMgr.Accounts = api.DefaultAccountsAPI{A: accMgr}
    // Expose limited plugin manager functions to API (wired after 'plugins' is constructed)
    stop := make(chan struct{})

    dumper := netdump.New(logger, cfg.Log.NetworkDump)

    // Plugins
    plugins := pman.NewManager()
    // Persist plugin enabled/disabled state in configured plugins dir
    plugins.SetSettingsDir(cfg.PluginsDir)
    plugins.Register(locate.New(cfg))
    plugins.Register(pluglog.New(logger))
    plugins.Register(plugstats.New(statProv))
    plugins.Register(plugears.New())
    plugins.Register(plugradio.New(cfg))
    plugins.Register(plugrecord.New(cfg))
    plugins.Register(plugauth.New(cfg))
    // attach plugin manager to stats provider for plugin totals
    if lp, ok := interface{}(statProv).(interface{ SetPluginManager(*pman.Manager) }); ok {
        lp.SetPluginManager(plugins)
    }
    apiMgr.PluginNames = func() []string { return plugins.Names() }
    apiMgr.EnabledPluginNames = func() []string { return plugins.EnabledNames() }
    apiMgr.SetPluginEnabled = func(name string, on bool) bool { return plugins.Enable(name, on) }
    apiMgr.PluginProcess = plugins.ProcessPluginApi

    if cfg.HttpListener {
        addr := fmt.Sprintf("0.0.0.0:%d", cfg.OpenJabNabServers.ListeningHttpPort)
        hb := httpbridge.New(addr, logger, apiMgr)
        // inject plugins into adapter
        if adapter, ok := hb.API.(*httpbridge.Adapter); ok { adapter.Plugins = plugins }
        hb.Dump = dumper.Log
        go func() { _ = hb.ListenAndServe(stop) }()
        logger.Info("httpbridge listening", slog.String("addr", addr))
    } else {
        logger.Warn("HTTP listener disabled by config")
    }

    if cfg.HttpNativeListener {
        nh := &nhttp.Server{API: apiMgr, Logger: logger, Plugins: plugins, Addr: fmt.Sprintf(":%d", cfg.NativeHttpPort)}
        go func(){ _ = nh.Start() }()
        logger.Info("native http listening", slog.Int("port", cfg.NativeHttpPort))
    }

    if cfg.XmppListener {
        xaddr := fmt.Sprintf("0.0.0.0:%d", cfg.OpenJabNabServers.ListeningXmppPort)
        xs := &xmpp.Server{Addr: xaddr, Domain: cfg.OpenJabNabServers.XmppServer, Logger: logger}
        xs.OnConnect = func(id string) { if id != "" { bunMgr.Connect(id); logger.Info("bunny connected", slog.String("id", id)) } }
        xs.OnDisconnect = func(id string) { if id != "" { bunMgr.Disconnect(id); logger.Info("bunny disconnected", slog.String("id", id)) } }
        xs.OnButton = func(id string, clicks int) {
            plugins.OnButton(id, clicks)
            logger.Info("button", slog.String("id", id), slog.Int("clicks", clicks))
        }
        xs.OnEars = func(id string, left, right int) {
            plugins.OnEars(id, left, right)
            logger.Info("ears", slog.String("id", id), slog.Int("left", left), slog.Int("right", right))
        }
        xs.GetPassword = accMgr.GetPassword
        xs.BypassAuth = cfg.Auth.Bypass
        xs.Dump = dumper.Log
        xs.OnRegistered = func(id, resource string) { logger.Info("bunny registered", slog.String("id", id), slog.String("resource", resource)) }
        // Set send function now that xs exists
        sendFunc = xs.SendPacket
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
