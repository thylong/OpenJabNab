package main

import (
    "fmt"
    "log/slog"
    "time"

    "OpenJabNab/internal/api"
    configpkg "OpenJabNab/internal/config"
    "OpenJabNab/internal/server/httpbridge"
    "OpenJabNab/internal/stats"
)

type servers struct {
	stop chan struct{}
}

func startServers(logger *slog.Logger, cfg *configpkg.Config) (*servers, error) {
    apiMgr := &api.Manager{Logger: logger, Stats: stats.Null{}}
    stop := make(chan struct{})

    if cfg.HttpListener {
        addr := fmt.Sprintf("127.0.0.1:%d", cfg.OpenJabNabServers.ListeningHttpPort)
        hb := httpbridge.New(addr, logger, apiMgr)
        go func() { _ = hb.ListenAndServe(stop) }()
        logger.Info("httpbridge listening", slog.String("addr", addr))
    } else {
        logger.Warn("HTTP listener disabled by config")
    }

    return &servers{stop: stop}, nil
}

func (s *servers) shutdown(logger *slog.Logger) {
	close(s.stop)
	time.Sleep(200 * time.Millisecond)
}
