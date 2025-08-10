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
    VisualName() string
    Type() PluginType
    Enabled() bool
    SetEnabled(bool)

    HttpRequestBefore(*Request)
    HttpRequestHandle(*Request) bool
    HttpRequestAfter(*Request)
}

// Optional XMPP event handlers
type ButtonHandler interface {
    OnButton(bunnyID string, clicks int)
}

type EarsHandler interface {
    OnEars(bunnyID string, left, right int)
}

// Optional API handler for /ojn_api/plugin/<name>/<function>
type ApiHandler interface {
    // If function matches, return handled=true and XML fragment
    ProcessPluginApi(function string, get map[string]string) (handled bool, xml []byte, err error)
}

// Optional lifecycle/event hooks
type ConnectHandler interface {
    OnConnect(bunnyID string)
}

type DisconnectHandler interface {
    OnDisconnect(bunnyID string)
}

// RFIDHandler is called when an RFID/Ztamp tag is read by a bunny
// tag is the raw tag identifier as a hexadecimal string when available
type RFIDHandler interface {
    OnRFID(bunnyID string, tag string)
}

// CronHandler is periodically invoked for background work
type CronHandler interface {
    OnCron()
}

// PacketSenderAware allows the manager to inject a safe send-to-bunny function
type PacketSenderAware interface {
    SetPacketSender(func(bunnyID string, payload []byte) bool)
}

// PluginTypeName returns a human-readable name for the plugin type
func PluginTypeName(t PluginType) string {
    switch t {
    case RequiredPlugin:
        return "Required"
    case SystemPlugin:
        return "System"
    case BunnyPlugin:
        return "Bunny"
    case ZtampPlugin:
        return "Ztamp"
    case BunnyZtampPlugin:
        return "BunnyZtamp"
    default:
        return "Unknown"
    }
}
