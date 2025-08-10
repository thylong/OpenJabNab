package plugin

// PluginType categorizes plugins for routing and enablement.
type PluginType int

const (
    RequiredPlugin PluginType = iota
    SystemPlugin
    BunnyPlugin
    ZtampPlugin
    BunnyZtampPlugin
)

// Request is a minimal HTTP-like request used by plugins.
type Request struct {
    URI     string
    RawURI  string
    Get     map[string]string
    Post    map[string]string
    RawPost []byte
    Reply   []byte
}

// Plugin defines the plugin interface.
type Plugin interface {
    Name() string
    Type() PluginType
    Enabled() bool
    SetEnabled(bool)

    HttpRequestBefore(*Request)
    HttpRequestHandle(*Request) bool
    HttpRequestAfter(*Request)
}
