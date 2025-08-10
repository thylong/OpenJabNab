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
