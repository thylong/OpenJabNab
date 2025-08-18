package netdump

import (
	"log/slog"
)

type Dumper struct{
	logger *slog.Logger
	enabled bool
}

func New(logger *slog.Logger, enabled bool) *Dumper {
	return &Dumper{logger: logger, enabled: enabled}
}

func (d *Dumper) Log(category string, data []byte) {
	if !d.enabled { return }
    // Log a truncated preview of the payload for troubleshooting
    preview := data
    if len(preview) > 512 { preview = preview[:512] }
    d.logger.Info("netdump", slog.String("cat", category), slog.Int("bytes", len(data)), slog.String("data", string(preview)))
}
