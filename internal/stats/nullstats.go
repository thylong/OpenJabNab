package stats

type Null struct{}

func (Null) BunnyTotals() (int, int) { return 0, 0 }
func (Null) ZtampTotal() int { return 0 }
func (Null) PluginTotals() (int, int) { return 0, 0 }
