package logging

import (
    "context"
    "log/slog"
    "os"
    "path/filepath"
    "strings"

    lumberjack "gopkg.in/natefinch/lumberjack.v2"
)

type closerFunc func()

// Init creates a slog logger with separate levels for stdout and file, and log rotation for the file.
// Rotation defaults: 50MB per file, 3 backups, 14 days.
func Init(logFile string, screenLevelStr, fileLevelStr string) (*slog.Logger, closerFunc, error) {
    // Ensure directory exists if a path is provided
    if dir := filepath.Dir(logFile); dir != "." && dir != "" {
        if err := os.MkdirAll(dir, 0o755); err != nil {
            return nil, nil, err
        }
    }

    // Build handlers with different levels
    screenLevel := parseLevel(screenLevelStr)
    fileLevel := parseLevel(fileLevelStr)

    // If logFile points to stdout or is empty, do not configure file logging
    lower := strings.ToLower(strings.TrimSpace(logFile))
    if lower == "" || lower == "/dev/stdout" || lower == "stdout" || lower == "-" {
        // Honor fileLevel when logging to stdout-only, so users can set LogFileLevel=Info and see logs
        effectiveLevel := screenLevel
        if fileLevel < screenLevel { effectiveLevel = fileLevel }
        screenHandler := slog.NewTextHandler(os.Stdout, &slog.HandlerOptions{Level: effectiveLevel})
        logger := slog.New(screenHandler)
        return logger, func() {}, nil
    }

    screenHandler := slog.NewTextHandler(os.Stdout, &slog.HandlerOptions{Level: screenLevel})

    fileWriter := &lumberjack.Logger{
        Filename:   logFile,
        MaxSize:    50, // megabytes
        MaxBackups: 3,
        MaxAge:     14, // days
        Compress:   true,
    }
    fileHandler := slog.NewTextHandler(fileWriter, &slog.HandlerOptions{Level: fileLevel})
    mh := multiHandler{handlers: []slog.Handler{fileHandler, screenHandler}}
    logger := slog.New(mh)
    return logger, func() { _ = fileWriter.Close() }, nil
}

func parseLevel(s string) slog.Level {
    switch strings.ToLower(s) {
    case "debug":
        return slog.LevelDebug
    case "info":
        return slog.LevelInfo
    case "warn", "warning":
        return slog.LevelWarn
    case "error":
        return slog.LevelError
    default:
        return slog.LevelInfo
    }
}

type multiHandler struct{ handlers []slog.Handler }

func (m multiHandler) Enabled(ctx context.Context, l slog.Level) bool {
    for _, h := range m.handlers {
        if h.Enabled(ctx, l) { return true }
    }
    return false
}

func (m multiHandler) Handle(ctx context.Context, r slog.Record) error {
    for _, h := range m.handlers { _ = h.Handle(ctx, r) }
    return nil
}

func (m multiHandler) WithAttrs(attrs []slog.Attr) slog.Handler {
    out := make([]slog.Handler, 0, len(m.handlers))
    for _, h := range m.handlers { out = append(out, h.WithAttrs(attrs)) }
    return multiHandler{handlers: out}
}

func (m multiHandler) WithGroup(name string) slog.Handler {
    out := make([]slog.Handler, 0, len(m.handlers))
    for _, h := range m.handlers { out = append(out, h.WithGroup(name)) }
    return multiHandler{handlers: out}
}
