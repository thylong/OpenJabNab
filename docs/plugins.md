## Plugins: APIs and settings

This document summarizes the available plugin APIs, their minimal behaviors, and the per‑plugin INI settings used by each plugin. All plugin settings are stored as INI files at `PluginsDir/plugin_<name>.ini`.

- PluginsDir defaults:
  - `OJN_PLUGIN_DIR` environment variable, else `/config/plugins` (if present), else local `plugins/`.
- Plugin enable/disable persistence:
  - Stored in each plugin’s INI under section `plugin`, key `enabled` (boolean).
  - Example: in `plugins/plugin_logger.ini` → `[plugin]\nenabled = false`.

## Common admin and settings APIs

- List all plugins: `/ojn_api/plugins-list`
- List enabled plugins: `/ojn_api/plugins-enabled`
- Enable a plugin: `/ojn_api/plugin-enable/<name>`
- Disable a plugin: `/ojn_api/plugin-disable/<name>`
- Per‑plugin settings helpers:
  - Get: `/ojn_api/plugin/<name>/getsetting?key=<k>` → `<value>...</value>`
  - Set: `/ojn_api/plugin/<name>/setsetting?key=<k>&value=<v>` → `<ok/>`
  - List keys: `/ojn_api/plugin/<name>/listsettings` → `<list><item>k</item>...</list>`
  - Delete: `/ojn_api/plugin/<name>/delsetting?key=<k>` → `<ok/>`

## Plugin: ears

- Purpose: track and set bunny ear positions.
- APIs:
  - Get last known position: `/ojn_api/plugin/ears/get?bunny=<id>` → `<left>..</left><right>..</right>`
  - Set position (sends packet over XMPP): `/ojn_api/plugin/ears/set?bunny=<id>&left=<0-15>&right=<0-15>` → `<ok/>` or `<error>Not connected</error>`
- INI settings: none specific (enabled flag only).

## Plugin: webradio

- Purpose: manage web radio stations and simple per‑bunny play/stop state.
- APIs:
  - List stations: `/ojn_api/plugin/webradio/list` → `<list><item name="NAME">URL</item>...</list>`
  - Add station: `/ojn_api/plugin/webradio/add?name=<NAME>&url=<URL>` → `<ok/>`
  - Delete station: `/ojn_api/plugin/webradio/del?name=<NAME>` → `<ok/>`
  - Set station for bunny: `/ojn_api/plugin/webradio/set?bunny=<id>&name=<NAME>` → `<ok/>`
  - Status for bunny: `/ojn_api/plugin/webradio/status?bunny=<id>` → `<playing>true|false</playing><station>NAME</station>`
  - Play/Stop for bunny: `/ojn_api/plugin/webradio/(play|stop)?bunny=<id>` → `<ok/>`
- INI settings (stored in `plugin_webradio.ini`):
  - Global stations: section `[stations]`, keys are station names, values are URLs.
  - Per‑bunny state: section `[bunny_<id>]` with keys `station` and `playing`.

## Plugin: record

- Purpose: minimal record control and state tracking (no audio capture here).
- APIs:
  - Start recording: `/ojn_api/plugin/record/start?bunny=<id>[&seconds=N]` → `<ok/>`
  - Stop recording: `/ojn_api/plugin/record/stop?bunny=<id>` → `<ok/>`
  - Status: `/ojn_api/plugin/record/status?bunny=<id>` → `<recording>true|false</recording><seconds>N</seconds><started>RFC3339</started>`
- INI settings (stored per bunny in `plugin_record.ini`): section `[bunny_<id>]` with keys `recording`, `seconds`, `started`.

## Plugin: auth

- Purpose: expose legacy authentication method selection API (parity); actual XMPP auth handled by server.
- APIs:
  - List methods: `/ojn_api/plugin/auth/getListOfAuthMethods` → `<list><item>DIGEST-MD5</item><item>PLAIN</item><item>BOTH</item></list>`
  - Set method: `/ojn_api/plugin/auth/setAuthMethod?name=PLAIN|DIGEST-MD5|BOTH` → `<ok/>`
  - Get method: `/ojn_api/plugin/auth/getAuthMethod` → `<method>...</method>`
- INI settings: section `[auth]`, key `method` with values `PLAIN`, `DIGEST-MD5`, or `BOTH`.

## Plugin: tts

- Purpose: synthesize text to audio via pluggable providers and play on bunny.
- APIs:
  - List voices: `/ojn_api/plugin/tts/voices` → `<list><item id="..." lang=".." gender="..">Name</item>...</list>`
  - Set per-bunny voice: `/ojn_api/plugin/tts/setvoice?bunny=<id>&voice=<providerVoiceId>` → `<ok/>`
  - Get per-bunny voice: `/ojn_api/plugin/tts/getvoice?bunny=<id>` → `<voice>..</voice>`
  - Speak: `/ojn_api/plugin/tts/speak?bunny=<id>&text=... [&voice=...]` → `<ok id="job-id"/>` (returns job id)
  - Job status: `/ojn_api/plugin/tts/status?id=<job-id>` → `<job id=".."><state>queued|running|done|error|dropped</state>[<url>..</url>]</job>`
  - Queue stats: `/ojn_api/plugin/tts/queue` → `<queue size="N" capacity="M"/>`
  - Metrics: `/ojn_api/plugin/tts/stats` → queued/running/completed, errors, cache hits/miss, average synth time
  - Health: `/ojn_api/plugin/tts/health` → `<status>ok|degraded</status>`
- Storage & URLs:
  - Files are written under `broadcast/tts/<voice>/<sha1(provider:voice:normalizedText)>.<ext>`
  - Device playback uses `MU <broadcast url>\nMW\n` over XMPP.
- Configuration:
  - In `openjabnab.ini` `[Config]` section:
    - `TTS = google | acapela | mock`
  - In `[TTS]` section:
    - `TimeoutMs` (default 15000), `MaxRetries` (2), `BackoffMs` (200), `RateLimitRPS` (5), `Workers` (2), `QueueSize` (128)
    - `AllowedVoices` (comma-separated provider voice IDs to expose)
  - Per-plugin INI (`plugins/plugin_tts.ini`):
    - `[plugin] AllowedVoices=...` and `DefaultVoice=...`
- Providers:
  - Google Cloud TTS:
    - Set env `GOOGLE_APPLICATION_CREDENTIALS` to service account JSON
    - Quotas/limits apply per GCP project; configure timeouts and rate limits via `[TTS]`
    - Kubernetes: mount a secret as a file and set `GOOGLE_APPLICATION_CREDENTIALS` to that path. Docker Compose: bind-mount the JSON into the container and set the env var.
  - Acapela:
    - Set env `ACAPELA_LOGIN`, `ACAPELA_PASSWORD`, `ACAPELA_APPLICATION`, optional `ACAPELA_BASE_URL`
    - Respect provider quotas; configure timeouts and rate limits via `[TTS]`
    - Kubernetes: store credentials in a Secret and map to env vars. Docker Compose: provide env vars in the service definition.
- Security:
  - Read secrets from env/volumes only; credentials are never logged.

## Samples and Docker/env

- Example `[TTS]` config snippet in `openjabnab.ini`:

```
[Config]
TTS = google

[TTS]
TimeoutMs = 15000
MaxRetries = 2
BackoffMs = 200
RateLimitRPS = 5
Workers = 2
QueueSize = 128
AllowedVoices = en-US-Standard-A,en-US-Standard-B
```

- Docker environment variables:
  - Mount `openjabnab.ini` to `/config/openjabnab.ini`
  - Set `OJN_PLUGIN_DIR=/config/plugins`, `OJN_STATE_DIR=/config/state`
  - For Google: mount service account JSON and set `GOOGLE_APPLICATION_CREDENTIALS=/secrets/gcp-sa.json`
  - For Acapela: set `ACAPELA_LOGIN`, `ACAPELA_PASSWORD`, `ACAPELA_APPLICATION`

- RealHttpRoot/broadcast mapping:
  - Ensure `RealHttpRoot` points to the http-wrapper root (e.g., `/app/http-wrapper/ojn_local/`)
  - Generated audio will be under `<RealHttpRoot>/broadcast/tts/...` and served by the wrapper or native HTTP.

## Deployment (Ops) examples

### Kubernetes (Google provider)

```
apiVersion: v1
kind: Secret
metadata:
  name: gcp-tts-sa
type: Opaque
stringData:
  gcp-sa.json: |
    { ... service account json ... }
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: openjabnab
spec:
  replicas: 1
  selector:
    matchLabels: { app: openjabnab }
  template:
    metadata:
      labels: { app: openjabnab }
    spec:
      containers:
      - name: ojn
        image: yourrepo/openjabnab:latest
        env:
        - name: GOOGLE_APPLICATION_CREDENTIALS
          value: /secrets/gcp-sa.json
        - name: OJN_PLUGIN_DIR
          value: /config/plugins
        - name: OJN_STATE_DIR
          value: /config/state
        volumeMounts:
        - name: gcp-sa
          mountPath: /secrets
          readOnly: true
        - name: config
          mountPath: /config
      volumes:
      - name: gcp-sa
        secret:
          secretName: gcp-tts-sa
          items:
          - key: gcp-sa.json
            path: gcp-sa.json
      - name: config
        persistentVolumeClaim:
          claimName: ojn-config-pvc
```

### Kubernetes (Acapela provider)

```
apiVersion: v1
kind: Secret
metadata:
  name: acapela-secrets
type: Opaque
stringData:
  ACAPELA_LOGIN: your_login
  ACAPELA_PASSWORD: your_password
  ACAPELA_APPLICATION: your_app
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: openjabnab
spec:
  replicas: 1
  selector:
    matchLabels: { app: openjabnab }
  template:
    metadata:
      labels: { app: openjabnab }
    spec:
      containers:
      - name: ojn
        image: yourrepo/openjabnab:latest
        envFrom:
        - secretRef: { name: acapela-secrets }
        env:
        - name: OJN_PLUGIN_DIR
          value: /config/plugins
        - name: OJN_STATE_DIR
          value: /config/state
        volumeMounts:
        - name: config
          mountPath: /config
      volumes:
      - name: config
        persistentVolumeClaim:
          claimName: ojn-config-pvc
```

### Docker Compose

```
services:
  openjabnab:
    image: yourrepo/openjabnab:latest
    ports:
      - "8080:8080"   # http bridge if exposed
      - "8081:8081"   # native http
      - "5222:5222"   # xmpp
    environment:
      OJN_PLUGIN_DIR: /config/plugins
      OJN_STATE_DIR: /config/state
      # Google
      GOOGLE_APPLICATION_CREDENTIALS: /secrets/gcp-sa.json
      # Acapela (alternative)
      # ACAPELA_LOGIN: your_login
      # ACAPELA_PASSWORD: your_password
      # ACAPELA_APPLICATION: your_app
    volumes:
      - ./config:/config
      - ./secrets:/secrets:ro
```

### Quotas and rate limits

- Google Cloud TTS: enforce quotas per project. Recommended initial `[TTS]` settings:
  - `RateLimitRPS = 5`, `Workers = 2`, `TimeoutMs = 15000`, `MaxRetries = 2`, `BackoffMs = 200`
- Acapela: respect service terms and throughput limits. Start with the same rate/worker defaults and adjust per observed latency.

### Secrets rotation and readiness

- Rotate provider secrets by updating the Kubernetes Secret (or Compose env) and rolling the Deployment.
- Prefer workload identity (e.g., GKE) for Google Cloud to avoid long‑lived keys.
- Add a readiness probe on `/ojn_api/plugin/tts/health` to ensure providers are reachable before routing traffic.

