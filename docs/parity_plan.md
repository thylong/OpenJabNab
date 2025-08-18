# OpenJabNab — Legacy Parity Plan

Goal: Reach behavior parity with legacy OJN so Nabaztag devices reliably fetch and play MU audio via our Go server.

## Workstream A — Boot and Init Parity

- Build full init packet
  - Implement packet builders mirroring legacy `Packet::GetData` for `AmbientPacket` and `SleepPacket`.
  - Minimal contents:
    - Ambient: header 0x7F FF FF FE + MoveLeft(0) + MoveRight(0) + Nose(No)
    - Sleep: Wake_Up
  - Expose plugin hook to let plugins alter the init packet (OnInit-style) before it’s serialized.
  - Acceptance: violet:iq:sources reply carries the structured base64 payload, and device accepts MU post-init.

- Dynamic bc.jsp (bootcode) parity
  - Serve MTL responses rather than a static file. Drive program states to push the device into the flow that triggers sources query and later MU acceptance.
  - Parse query params (`sn`, `v`, `m`, `l`, `p`, `h`) and return the right script.
  - Acceptance: device runs full boot choreography to `streaming` and accepts MU.

## Workstream B — Transport/Command Parity

- MU pipeline toggles (config-driven)
  - `[Bunny] UseAbsoluteMU`: absolute (http[s]://host/broadcast/...) vs relative (`broadcast/...`).
  - `[Bunny] InsertPL3`: send `PL 3` after MU (some firmwares expect it).
  - `[Bunny] SendST`: append `ST` after `MW`.
  - `[Bunny] CmdDelayMs`: spacing between MU/MW/(PL3)/ST.
  - Acceptance: toggles change behavior without rebuild; logs show effective variant.

- Stanza variants (feature flags)
  - Sender and message type:
    - Default: `from='net.openjabnab.platform@<domain>/services'` (no type attr)
    - Optional: `from='net.violet.platform@<domain>/vl'`, `type='chat'`
  - Acceptance: switchable at runtime; netdump reflects selection.

## Workstream C — HTTP Parity

- Paths and headers
  - Serve both `/broadcast/...` and `/ojn_local/broadcast/...`.
  - Content-Type: `audio/mpeg`; ensure `Content-Length` set; avoid chunked for HTTP/1.0 clients.
  - Optional strict HTTP/1.0 mode for User-Agent `MTL`.
  - Acceptance: direct HTTP/1.0 GET yields 200, correct headers.

- locate.jsp parity
  - Reply lines:
    - `ping <xmppHost>`
    - `broad <broadHost>` (bare host; if BroadServer is URL, extract host)
    - `xmpp_domain <xmppHost>:<port>`
  - Acceptance: device continues to GET locate.jsp; values match expectations.

## Workstream D — Tests and Tooling

- Integration tests
  - Emulate: stream → auth → bind → session → sources IQ → init payload → MU acceptance check (mock client asserts MU order/delays/contents).
  - Assert a mock bunny performs a GET for the broadcast path on speak.
  - Acceptance: toggled permutations (PL3 on/off, absolute vs relative MU, stanza variants) pass.

- Autotest harness (for real hardware)
  - Scripted loop over config variants:
    - Restart stack, wait for reconnect, trigger speak, capture tcpdump traffic for bunny IP.
    - Success criterion: observe GET to `/broadcast/` or `/ojn_local/broadcast/`.
  - Output a matrix of variant → success/failure.

## Workstream E — Observability and Safety

- Logging
  - Structured logs for: bc.jsp decisions; sources reply (length and base64 short hash); MU variant used; stanza IDs; timing between packets.
- Metrics
  - MU→HTTP GET success ratio within N seconds; count cache hits/misses, per-bunny outcomes.
  - Simple health on init pipeline.

## Workstream F — Rollout

- Defaults favor legacy-closest path (relative MU, CRLF, small delays, unique IDs, legacy sender).
- Document flags in README/docs; provide a "LegacyStrict" preset for troubleshooting.

## Execution Order (incremental)

1) Finalize packet builders and the init reply (A1).  
2) Add MU pipeline toggles and `PL 3` option (B1).  
3) Implement dynamic bc.jsp minimal viable MTL (A2).  
4) Confirm locate host handling (C).  
5) Build integration tests + autotest harness (D).  
6) Observability/metrics (E).  
7) Iterate stanza variants if needed (B2).

## Acceptance (end-to-end)

- After cold boot, within ~10s of "bunny connected/registered":
  - One speak sends MU (and configured PL/MW/ST) with configured delays.
  - tcpdump shows bunny GET to broadcast path.
  - Bunny plays audio.

- Non-functional
  - All behavior toggles are runtime-configurable via INI.
  - CI tests green; autotest consistently identifies a working profile.
