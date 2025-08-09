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
	d.logger.Info("netdump", slog.String("cat", category), slog.Int("bytes", len(data)))
}
