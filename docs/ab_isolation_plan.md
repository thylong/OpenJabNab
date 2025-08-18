# Strong A/B Isolation Plan (Legacy vs Go Server)

Purpose: Rapidly isolate which differences prevent MU playback by testing controlled variants and capturing objective signals (XMPP stanzas, HTTP GETs) on real hardware.

## Signals and Success Criteria
- XMPP: "XMPP To Bunny" lines for MU/MW(/PL3/ST) with correct resource and sender.
- HTTP: tcpdump shows GET from bunny IP to `/broadcast/tts/...mp3` or `/ojn_local/broadcast/tts/...mp3` within N seconds of MU.
- Device: audible playback.

Success = HTTP GET observed (primary); audible playback (secondary).

## Global Test Protocol
- Pre-conditions
  - Bunny and server on same 2.4 GHz SSID; client isolation off.
  - Server port 80 reachable from bunny.
- Per-variant cycle
  1) Apply config (INI) and restart stack.
  2) Power-cycle bunny, wait for register/streaming.
  3) Trigger a single speak.
  4) Capture tcpdump for 15–20s.
  5) Record XMPP stanzas and GET presence/absence.
- Cooldown: 10–15s between variants to avoid rate limiting.

## Variant Dimensions (A/B Factors)
- MU URL form
  - A: absolute `http://<broadHost>/broadcast/...`
  - B: relative `broadcast/...`
- Legacy path prefix
  - A: `/broadcast/...`
  - B: `/ojn_local/broadcast/...`
- Insert PL after MU
  - A: none
  - B: `PL 3` between MU and MW
- Send ST after MW
  - A: off
  - B: on
- Line endings and pacing
  - A: `\n`, no delay
  - B: `\r\n` + 150 ms delay between commands
- Sender JID and message type
  - A: `net.openjabnab.platform@<domain>/services` (no type)
  - B: `net.violet.platform@<domain>/vl` + `type='chat'`
- Resource
  - A: accept `boot`
  - B: force `streaming`
- Sources/init payload
  - A: minimal non-empty
  - B: faithful Ambient+Sleep framed packet
- bc.jsp
  - A: static bootcode
  - B: dynamic MTL flow (minimal viable)
- Broad host advertised by locate.jsp
  - A: LAN IP (bare host)
  - B: domain (split DNS)

## Minimal Variant Matrix (ordered by impact)
1) Baseline (current best): absolute MU + CRLF + 150 ms + unique IDs + legacy sender + resource `streaming` + faithful init + `/broadcast` + locate broad=LAN IP.
2) Same + `PL 3`.
3) Switch to `/ojn_local/broadcast`.
4) Relative MU.
5) Sender variant (`net.violet.platform...` + type=chat).
6) No delays.
7) bc.jsp: dynamic MTL on.

Stop at first success.

## Automation
- Script: `scripts/ojn_autotest.sh` (see separate doc/implementation)
  - Inputs: `BUNNY_ID`, `BUNNY_IP`, `SERVER_HOST`.
  - Loop: apply INI changes → restart (`docker compose -f docker-compose.stack.yml up --force-recreate --remove-orphans -d`) → wait → speak → tcpdump.
  - Detect GET regex: `GET /(ojn_local/)?broadcast/tts/.*\.mp3`.
  - Output: per-variant logs and a summary table.

## Data to Capture per Variant
- INI diffs.
- Netdump excerpts: sources reply (base64 length/hash), MU/MW(/PL/ST), stanza IDs, sender, resource.
- tcpdump snippet (or absence marker).
- Timestamped result (success/failure) and notes.

## Analysis Workflow
- If any variant succeeds → diff against baseline to identify the causal factor; lock in flags.
- If none succeed → enable dynamic bc.jsp and retry top 3 variants; if still no → inspect device-specific firmware notes, raise packet-level logging, and capture full XMPP exchange to compare with legacy server.

## Safety and Controls
- Rate-limit variants (one speak per 20–30s) to avoid provider rate limits and device overwhelm.
- Keep speak text constant to leverage TTS cache; ensures consistent MU path.

## Roll-forward Plan
- Promote successful flags to defaults for affected firmware cohort.
- Persist working profile per bunny ID (optional), auto-apply on connect.

## Rollback Plan
- All toggles are INI-controlled; revert to defaults if regressions observed.

## Ownership and Next Steps
- Implement PL3/flag toggles and bc.jsp minimal dynamic flow.
- Wire autotest results exporter (CSV/Markdown) and store under `autotest_logs/`.
