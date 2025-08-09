## OpenJabNab — Migration Plan to Go

### Goals and assumptions
- Maintain feature parity with the existing C++/Qt server and PHP HTTP wrapper during the transition.
- Prioritize maintainability, testability, and observability; keep device compatibility (HTTP bridge protocol and XMPP behaviors).
- Incremental rollout with clear acceptance criteria per phase; allow A/B testing alongside the C++ server.

### Current architecture (what we are porting)
- Core app responsible for:
  - Loading global config from `openjabnab.ini`, log rotation, startup/shutdown lifecycle.
  - TCP listeners: custom HTTP bridge and XMPP.
- Custom HTTP bridge protocol spoken by the PHP wrapper to the server over TCP:
  - Length-prefixed frame (4-byte little-endian total size), followed by 1-byte type: 1=GET, 2=POST, 3=POSTRAW.
  - Raw headers (until `\0`), then raw URI (until `\0`), then raw POST bytes.
  - Server parses, routes to API or plugins, returns XML or raw bytes; optionally forwards HTTP externally.
- XMPP handler with simplified SASL and IQ handling:
  - Stream negotiation and `currentAuthStep`-driven auth (PLAIN/DIGEST-MD5 subset) via the `auth` plugin.
  - Handles `<message>` (button/ears), `<iq>` bind/session/sources/version, `<presence>`, and 1-byte ping.
- Plugin system:
  - Lifecycle: `Init`, enable/disable (persisted), API registration.
  - HTTP hooks: `HttpRequestBefore`, `HttpRequestHandle` (short-circuit), `HttpRequestAfter`.
  - XMPP hooks: `XmppBunnyMessage`, `OnClick`, `OnEarsMove`, `OnRFID`, connect/disconnect, cron.
  - Plugin settings via per-plugin INI files in `plugins/plugin_<name>.ini`.
- API router:
  - XML responses and "Violet" XML for `/ojn/FR/api`. Namespaces and response structure must remain compatible.
- TTS providers via HTTP (Google, Acapela) storing generated audio into public/broadcast paths.
- Logging and network dumps.

### Go architecture mapping
- Qt signals/slots → goroutines and channels; `context.Context` for cancellation.
- QTcpServer/QTcpSocket → `net.Listener`, per-connection goroutines; deadlines to avoid leaks.
- QSettings (INI) → `gopkg.in/ini.v1` with typed getters mirroring existing keys.
- Dynamic Qt plugins → statically-linked plugin registry (build tags to include/exclude). Optionally, later support process-based plugins (gRPC) for hot-reload.
- XML/regex → `encoding/xml` + `regexp` (minimize regex; prefer struct-based XML).

### Proposed Go project layout
- `cmd/openjabnab/` — main entrypoint and wiring.
- `internal/config/` — INI loader, typed accessors, defaults, and feature flags.
- `internal/logging/` — slog-backed logger and daily rotation integration.
- `internal/netdump/` — structured network dumps mirroring current categories.
- `internal/server/httpbridge/` — TCP server implementing the PHP-wrapper protocol; request model and routing.
- `internal/server/xmpp/` — minimal XMPP server with the existing state machine and messages.
- `internal/api/` — API router and XML/Violet XML encoders.
- `internal/plugin/` — plugin interfaces, manager, registry, settings, enable/disable state.
- `internal/bunny/`, `internal/ztamp/`, `internal/packet/` — domain logic and message/packet formation.
- `internal/account/` — account manager and access control.
- `internal/cron/` — daily tasks (log rotation) and scheduled callbacks.
- `internal/tts/` — TTS providers (Google, Acapela) with pluggable interface.
- `web/` — optional static files for plugin HTTP assets (keep broadcast path compatibility).

### Protocol compatibility (must keep exact behavior)
- HTTP bridge frame:
  - 4-byte little-endian frame length equals `len(frame)`.
  - 1-byte type: `{1: GET, 2: POST, 3: POSTRAW}`.
  - Headers bytes until `\0`, then URI bytes until `\0`, then raw POST.
  - Preserve header behavior: copy, drop `Connection`, set `Host` if forwarding.
- XMPP:
  - Mirror the same `currentAuthStep` transitions, including PLAIN and DIGEST-MD5 subset used by devices.
  - Support IQs: bind (resource capture), session, sources/version, unbind `boot` (triggers `Ready`), presence echo, 1-byte ping logic triggering stream version check when resource is `streaming`.
- File/broadcast paths:
  - Keep output and broadcast URL patterns stable, e.g., `broadcast/<httpFolder>/<file>`.

### Key Go interfaces (sketch)
```go
// internal/plugin/plugin.go
package plugin

type ClickType int
const (
    SingleClick ClickType = iota
    DoubleClick
)

type PluginType int
const (
    RequiredPlugin PluginType = iota
    SystemPlugin
    BunnyPlugin
    ZtampPlugin
    BunnyZtampPlugin
)

type HTTPRequest struct {
    Method   string
    RawURI   string
    URI      string
    Headers  map[string]string
    Get      map[string]string
    Post     map[string]string
    RawPost  []byte
    Reply    []byte
}

type Context interface {
    Config() Config
    Logger() Logger
    // Domain services accessors (bunny manager, account manager, etc.)
}

type Plugin interface {
    Name() string
    VisualName() string
    Type() PluginType
    Init(Context) error
    Enabled() bool
    SetEnabled(bool)
    HttpRequestBefore(*HTTPRequest)
    HttpRequestHandle(*HTTPRequest) bool
    HttpRequestAfter(*HTTPRequest)
    XmppBunnyMessage(b *bunny.Bunny, raw []byte)
    OnClick(b *bunny.Bunny, t ClickType) bool
    OnEarsMove(b *bunny.Bunny, left, right int) bool
    // Optional: OnRFID, OnBunnyConnect/Disconnect, OnZtampConnect/Disconnect, OnCron
}
```

```go
// internal/server/httpbridge/decoder.go
func Decode(frame []byte) (*plugin.HTTPRequest, error) {
    if len(frame) < 5 { return nil, ErrShort }
    n := int(binary.LittleEndian.Uint32(frame[:4]))
    if n != len(frame) { return nil, ErrBadSize }
    t := frame[4]
    payload := frame[5:]
    i := bytes.IndexByte(payload, 0)
    if i < 0 { return nil, ErrMalformed }
    rawHeaders := string(payload[:i])
    payload = payload[i+1:]
    j := bytes.IndexByte(payload, 0)
    if j < 0 { return nil, ErrMalformed }
    rawURI := string(payload[:j])
    post := payload[j+1:]
    // parse headers and query string, populate HTTPRequest
    // ...
    return req, nil
}
```

### Migration phases and acceptance criteria
- Phase 0 — Scaffolding
  - Create Go module, config loader with INI parity, logging + daily rotation, netdump categories.
  - Acceptance: app starts, loads `openjabnab.ini`, rotates logs daily.

- Phase 1 — HTTP bridge TCP server + API router parity
  - Implement frame decoder/encoder, listener, and routing to API.
  - Implement `about`, `ping`, `stats` with identical XML; wire account/token mechanics.
  - Acceptance: PHP wrapper can call `/ojn_api/...` and `/ojn/FR/api...` and receive identical responses.

- Phase 2 — XMPP server and auth flow
  - Reproduce `PluginAuth::DoAuth` behavior and stream/IQ handling.
  - Acceptance: a device or test client completes auth, bind/session/sources, and reaches `Ready`.

- Phase 3 — Domain model and managers
  - Port Bunny, Ztamp, Account managers; persist and load states as needed.
  - Acceptance: API endpoints requiring these managers behave as before.

- Phase 4 — Plugins
  - Port required/system plugins first: `auth`, `locate`, `stats`, `record` (as necessary for core flows).
  - Then high-value bunny plugins: `tts`, `webradio`, `ears`, etc.
  - Acceptance: plugin listing/enable/disable works; plugin APIs functional.

- Phase 5 — TTS providers
  - Implement Google/Acapela providers and ensure file output/broadcast paths match.
  - Acceptance: generated audio is accessible via the same URLs.

- Phase 6 — Observability & ops
  - Metrics, structured logs, graceful shutdown (SIGINT/SIGTERM), config flags to enable/disable listeners.
  - Acceptance: clean shutdown and visibility comparable to current logs/dumps.

- Phase 7 — Optional native HTTP server
  - Replace PHP wrapper by exposing native HTTP endpoints with rewrite compatibility.
  - Acceptance: devices can call Go server directly; wrapper can be decomissioned.

### Dependencies
- Config: `gopkg.in/ini.v1`
- Logging: `log/slog` (std) or `uber-go/zap` (switchable)
- XML: `encoding/xml`
- HTTP client/server: `net/http`
- XMPP: custom minimal implementation over `net` (consider `mellium.im/xmpp` later if useful)

### Testing strategy
- Golden-file tests for XML and Violet XML responses to ensure parity.
- Protocol unit tests for HTTP bridge decoder/encoder; randomized frames and boundary cases.
- XMPP integration tests simulating auth sequence and IQ interactions.
- Plugin contract tests validating Before/Handle/After order and short-circuiting.
- Config round-trips with defaults and missing keys.

### Risks and decisions
- Runtime plugin loading: Go’s `plugin` package is not portable; we will use a static registry with build tags. If hot-reload is needed, consider process-based plugins (gRPC) later.
- DIGEST-MD5 is deprecated but preserved for device compatibility (behind a feature flag).
- Frame length endianness: standardize on little-endian as per x86 origin of current server.

### Timeline (est. 2–4 weeks)
- Week 1: Scaffolding, config/logging, HTTP bridge decoder, API router with `about/ping/stats`.
- Week 2: XMPP core and auth; Bunny init path; minimal required plugins.
- Week 3: TTS and system plugins; observability; parity tests.
- Week 4: Additional plugins; optional native HTTP; docs and rollout plan.

### Next steps
- Initialize `go.mod` and scaffolding under `cmd/openjabnab` and `internal/...`.
- Implement HTTP bridge decoding and minimal API endpoints (`about/ping/stats`).
- Wire config keys to mirror `openjabnab.ini` and per-plugin INI locations.

### Open choices to confirm (defaults in parentheses)
- Keep PHP wrapper initially? (yes)
- Logging library (`slog`) vs `zap`? (slog)
- Dynamic plugins now? (no; static registry with build tags)

---
This plan preserves device compatibility, allows incremental rollout, and maps existing Qt/C++ constructs to idiomatic, testable Go components. Update priorities (e.g., which plugins to port first) as needed.
