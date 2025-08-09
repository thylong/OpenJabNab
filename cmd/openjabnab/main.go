package main

import (
	"context"
	"fmt"
	"os"
	"os/signal"
	"syscall"

	"log/slog"

	config "OpenJabNab/internal/config"
	logging "OpenJabNab/internal/logging"
)

func main() {
	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	cfg, err := config.LoadDefault()
	if err != nil {
		fmt.Fprintf(os.Stderr, "failed to load config: %v\n", err)
		os.Exit(1)
	}

	logger, closeLogger, err := logging.Init(cfg.Log.LogFile)
	if err != nil {
		fmt.Fprintf(os.Stderr, "failed to init logger: %v\n", err)
		os.Exit(1)
	}
	defer closeLogger()

	logger.Info("OpenJabNab Go bootstrap",
		slog.String("logFile", cfg.Log.LogFile),
		slog.Bool("httpListener", cfg.HttpListener),
		slog.Bool("httpApi", cfg.HttpApi),
		slog.Bool("httpVioletApi", cfg.HttpVioletApi),
		slog.Bool("xmppListener", cfg.XmppListener),
	)

	// Block until we receive a shutdown signal. Additional servers will be wired next steps.
	<-ctx.Done()
	logger.Info("Shutting down")
}
