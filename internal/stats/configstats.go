package stats

import (
    configpkg "OpenJabNab/internal/config"
    "OpenJabNab/internal/bunny"
    "OpenJabNab/internal/ztamp"
)

type Live struct{
    cfg *configpkg.Config
    B *bunny.Manager
    Z *ztamp.Manager
}

func NewLive(cfg *configpkg.Config, b *bunny.Manager, z *ztamp.Manager) Live {
    return Live{cfg: cfg, B: b, Z: z}
}

func (l Live) BunnyTotals() (int, int) {
    total := l.cfg.MaxNumberOfBunnies
    connected := 0
    if l.B != nil { connected = l.B.ConnectedCount() }
    return total, connected
}

func (l Live) ZtampTotal() int {
    if l.Z == nil { return 0 }
    return l.Z.Count()
}

func (l Live) PluginTotals() (int, int) { return 0, 0 }
