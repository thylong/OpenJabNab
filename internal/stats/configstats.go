package stats

import configpkg "OpenJabNab/internal/config"

type ConfigStats struct{
	cfg *configpkg.Config
}

func NewConfigStats(cfg *configpkg.Config) ConfigStats { return ConfigStats{cfg: cfg} }

func (c ConfigStats) BunnyTotals() (int, int) {
	// Total from config, connected unknown at this stage
	return c.cfg.MaxNumberOfBunnies, 0
}

func (c ConfigStats) ZtampTotal() int { return 0 }

func (c ConfigStats) PluginTotals() (int, int) { return 0, 0 }
