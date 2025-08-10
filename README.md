OpenJabNab (Go)
===============

OpenJabNab is an open server for the Nabaztag/Tag™ connected bunny. It provides a drop‑in replacement for the legacy Violet servers, speaking the same XMPP protocol and HTTP bridge, with a modern plugin system and TTS capabilities.

Nabaztag is a trademark of Violet. OpenJabNab is not owned by or affiliated with Violet.

What you get
------------

- XMPP server compatible with Nabaztag devices (PLAIN/DIGEST-MD5, bind/session/sources, button/ears events)
- HTTP bridge (legacy TCP) and Native HTTP server for `/ojn_api/...` and device routes
- Plugin system with enable/disable persistence and per‑plugin INI settings
  - Core: `locate`, `stats`, `logger`, `auth`, `record`
  - Bunny: `ears` (set/get), `webradio` (stations, RFID), `tts` (text‑to‑speech)
- TTS providers with caching and broadcast URLs compatible with legacy wrappers
  - Google Cloud TTS and Acapela (plus a mock provider for dev/tests)
- Observability: structured logs; plugin and TTS job stats APIs

Project status
--------------

- The Go rewrite implements the most used flows end‑to‑end and is covered by unit/integration tests. Additional polish and admin UI can be layered on top.

Quick start
-----------

1) Build and run locally

```
go test ./...
go build ./cmd/openjabnab
./openjabnab
```

2) Configure your server

Create or edit `openjabnab.ini` (mounted to `/config/openjabnab.ini` in containers). Minimal example:

```
[Config]
httpListener = true
HttpNativeListener = true
xmppListener = true
RealHttpRoot = /app/http-wrapper/ojn_local/
TTS = mock  ; or google / acapela

[TTS]
RateLimitRPS = 5
Workers = 2
QueueSize = 128
RetentionDays = 30
```

3) Point your bunny to your server

- Set advanced settings to use `your.domain/vl` as the server, or via your router/DNS override pointing to this host.

TTS provider setup
------------------

- Google Cloud TTS:
  - Set env `GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json`
  - Optional: `GOOGLE_TTS_ENDPOINT` for regional endpoints
- Acapela:
  - Set env `ACAPELA_LOGIN`, `ACAPELA_PASSWORD`, `ACAPELA_APPLICATION` (and optional `ACAPELA_BASE_URL`)

Plugins and APIs
----------------

- Admin endpoints are available under `/ojn_api/...` (see `docs/plugins.md` for full details and examples):
  - Plugin list/enable/disable, per‑plugin settings helpers
  - Ears: get/set positions
  - Webradio: stations, play/stop, RFID mapping
  - TTS: voices, setvoice, speak (returns job id), status, queue, stats, health

Deployment
----------

- Native HTTP can serve static `broadcast/...` paths directly when `RealHttpRoot` is set.
- Kubernetes and Docker Compose examples (including secrets and volume mounts) are provided in `docs/plugins.md`.

Development
-----------

- Code layout:
  - `cmd/openjabnab` – main entrypoint
  - `internal/server/httpbridge`, `internal/server/xmpp`, `internal/server/nhttp`
  - `internal/api` – legacy API compatibility
  - `internal/plugin` and `internal/plugins/*` – plugin interfaces and implementations
  - `internal/tts` – provider interfaces and clients
- Run tests: `go test ./...`

License
-------

This software is provided under the GPL license. See `COPYING` for details.

Trademarks belong to their respective owners.
