package logging

import (
	"io"
	"log/slog"
	"os"
	"path/filepath"
)

type closerFunc func()

// Init creates a basic slog logger writing to both stdout and a file.
// It returns the logger and a Close func to release file resources.
func Init(logFile string) (*slog.Logger, closerFunc, error) {
	// Ensure directory exists if a path is provided
	if dir := filepath.Dir(logFile); dir != "." && dir != "" {
		if err := os.MkdirAll(dir, 0o755); err != nil {
			return nil, nil, err
		}
	}
	f, err := os.OpenFile(logFile, os.O_CREATE|os.O_WRONLY|os.O_APPEND, 0o644)
	if err != nil {
		return nil, nil, err
	}
	mw := io.MultiWriter(os.Stdout, f)
	h := slog.NewTextHandler(mw, &slog.HandlerOptions{Level: slog.LevelInfo})
	logger := slog.New(h)
	return logger, func() { _ = f.Close() }, nil
}
