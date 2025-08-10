package api

import (
    "strings"
    "OpenJabNab/internal/account"
    "OpenJabNab/internal/bunny"
    "encoding/base64"
)

type DefaultPluginAPI struct{}

type DefaultBunnyAPI struct{ B *bunny.Manager; Send func(id string, payload []byte) bool }

type DefaultZtampAPI struct{
    ZCount func() int
    List func() []string
    Add func(id string)
    Remove func(id string)
    Assign func(id, bunny string) bool
    Unassign func(id string)
    AssignedTo func(id string) (string, bool)
}

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
        switch fn {
        case "stats":
            online := false
            if d.B != nil { for _, c := range d.B.ListConnected() { if c == id { online = true; break } } }
            if online { return []byte(`<online>true</online>`), nil }
            return []byte(`<online>false</online>`), nil
        case "setname":
            if name := get["name"]; name != "" && d.B != nil {
                d.B.SetName(id, name)
                return []byte(`<ok/>`), nil
            }
            return []byte(`<error>Missing name</error>`), nil
        case "name":
            if d.B != nil {
                return []byte(`<name>` + d.B.GetName(id) + `</name>`), nil
            }
            return []byte(`<name/>`), nil
        case "sendpacket":
            data := get["data"]
            if data == "" { return []byte(`<error>Missing data</error>`), nil }
            // Expect base64 payload
            raw, err := base64.StdEncoding.DecodeString(data)
            if err != nil { return []byte(`<error>Invalid base64</error>`), nil }
            if d.Send != nil && d.Send(id, raw) { return []byte(`<ok/>`), nil }
            return []byte(`<error>Not connected</error>`), nil
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
            items := []string{}
            if d.List != nil { items = d.List() }
            out := "<list>"
            for _, id := range items { out += "<item>" + id + "</item>" }
            out += "</list>"
            return []byte(out), nil
        case "add":
            id := get["id"]
            if id == "" || d.Add == nil { return []byte(`<error>Missing id</error>`), nil }
            d.Add(id); return []byte(`<ok/>`), nil
        case "remove":
            id := get["id"]
            if id == "" || d.Remove == nil { return []byte(`<error>Missing id</error>`), nil }
            d.Remove(id); return []byte(`<ok/>`), nil
        }
    }
    if len(parts) == 2 {
        id, fn := parts[0], parts[1]
        switch fn {
        case "stats":
            if d.List != nil {
                known := false
                for _, k := range d.List() { if k == id { known = true; break } }
                if known { return []byte(`<known>true</known>`), nil }
                return []byte(`<known>false</known>`), nil
            }
        case "assign":
            bunny := get["bunny"]
            if bunny == "" || d.Assign == nil { return []byte(`<error>Missing bunny</error>`), nil }
            if d.Assign(id, bunny) { return []byte(`<ok/>`), nil }
            return []byte(`<error>Unknown ztamp</error>`), nil
        case "unassign":
            if d.Unassign == nil { return []byte(`<error>Unsupported</error>`), nil }
            d.Unassign(id); return []byte(`<ok/>`), nil
        case "assigned":
            if d.AssignedTo != nil {
                if b, ok := d.AssignedTo(id); ok { return []byte(`<bunny>` + b + `</bunny>`), nil }
                return []byte(`<bunny/>`), nil
            }
        }
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
        case "login":
            user := get["user"]
            pass := get["pass"]
            if user == "" || pass == "" { return []byte(`<error>Missing credentials</error>`), nil }
            if t, ok := d.A.IssueToken(user, pass); ok {
                return []byte(`<token>` + t + `</token>`), nil
            }
            return []byte(`<error>Invalid credentials</error>`), nil
        case "validate":
            if token == "" { return []byte(`<valid>false</valid>`), nil }
            if d.A.ValidateToken(token) { return []byte(`<valid>true</valid>`), nil }
            return []byte(`<valid>false</valid>`), nil
        case "logout":
            if token != "" { d.A.RevokeToken(token) }
            return []byte(`<ok/>`), nil
        case "list":
            return []byte(`<list/>`), nil
        }
    }
    if !d.A.HasAccess(token) { return []byte(`<error>Access denied</error>`), nil }
    return []byte(`<error>Unknown Accounts Api Call</error>`), nil
}
