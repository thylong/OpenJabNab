package api

import (
    "strings"
    "OpenJabNab/internal/account"
    "OpenJabNab/internal/bunny"
)

type DefaultPluginAPI struct{}

type DefaultBunnyAPI struct{ B *bunny.Manager }

type DefaultZtampAPI struct{ ZCount func() int }

type DefaultAccountsAPI struct{ A *account.Manager }

// Deprecated path; prefer /ojn_api/plugin/<name>/<function> handled by Api.Manager
func (d DefaultPluginAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
    return []byte(`<error>Plugin API not implemented</error>`), nil
}

func (d DefaultBunnyAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
    parts := strings.Split(strings.Trim(request, "/"), "/")
    if len(parts) == 1 {
        switch parts[0] {
        case "stats":
            connected := 0; capacity := 0
            if d.B != nil { connected = d.B.ConnectedCount(); capacity = d.B.Capacity() }
            return []byte(`<connected>` + itoa(connected) + `</connected><capacity>` + itoa(capacity) + `</capacity>`), nil
        case "list":
            ids := []string{}
            if d.B != nil { ids = d.B.ListConnected() }
            out := "<list>"
            for _, id := range ids { out += "<item>" + id + "</item>" }
            out += "</list>"
            return []byte(out), nil
        }
    }
    if len(parts) == 2 {
        id, fn := parts[0], parts[1]
        if fn == "stats" {
            online := false
            if d.B != nil { for _, c := range d.B.ListConnected() { if c == id { online = true; break } } }
            if online { return []byte(`<online>true</online>`), nil }
            return []byte(`<online>false</online>`), nil
        }
    }
    return []byte(`<error>Unknown Bunny Api Call</error>`), nil
}

func (d DefaultBunnyAPI) ProcessViolet(request string, get map[string]string) ([]byte, error) {
	return []byte(`<message>PONG</message><comment></comment>`), nil
}

func (d DefaultZtampAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
    parts := strings.Split(strings.Trim(request, "/"), "/")
    if len(parts) == 1 {
        switch parts[0] {
        case "stats":
            c := 0
            if d.ZCount != nil { c = d.ZCount() }
            return []byte(`<count>` + itoa(c) + `</count>`), nil
        case "list":
            return []byte(`<list/>`), nil
        }
    }
    if len(parts) == 2 {
        if parts[1] == "stats" { return []byte(`<known>false</known>`), nil }
    }
    return []byte(`<error>Unknown Ztamp Api Call</error>`), nil
}

func (d DefaultAccountsAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
    parts := strings.Split(strings.Trim(request, "/"), "/")
    if len(parts) == 1 {
        switch parts[0] {
        case "ping":
            return []byte(`<ok/>`), nil
        case "whoami":
            if token != "" { return []byte(`<token>` + token + `</token>`), nil }
            return []byte(`<token/>`), nil
        case "list":
            return []byte(`<list/>`), nil
        }
    }
    if !d.A.HasAccess(token) { return []byte(`<error>Access denied</error>`), nil }
    return []byte(`<error>Unknown Accounts Api Call</error>`), nil
}
