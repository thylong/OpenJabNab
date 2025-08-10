package xmpp

import (
    "crypto/rand"
    "encoding/base64"
    "encoding/hex"
    "log/slog"
    "regexp"
    "strconv"
)

type handler struct {
	domain string
	logger *slog.Logger
	step   int
	resource string
    bunnyID string
    onIdentify func(string)
    // Credentials (temporary static for validation)
    getPassword func(user string) (string, bool)
    nonce string
    nonceFactory func() string
    authUser string
    onButton func(id string, clicks int)
    onEars   func(id string, left, right int)
    bypassAuth bool
    onRegistered func(id, resource string)
}

func newHandler(domain string, logger *slog.Logger) *handler {
    return &handler{domain: domain, logger: logger}
}

var (
	reMessage = regexp.MustCompile(`(?s)<message[^>]*>(.*)</message>`) // greedy content
	reIQ      = regexp.MustCompile(`(?s)<iq.*</iq>`) // any iq
	rePresence= regexp.MustCompile(`<presence from='(.*)' id='(.*)'></presence>`) 
    reResponse= regexp.MustCompile(`(?s)<response[^>]*>(.*?)</response>`)
    reAuthPlain= regexp.MustCompile(`(?s)<auth[^>]*mechanism=['\"]PLAIN['\"][^>]*>(.*?)</auth>`)
)

func (h *handler) Process(in []byte) (out []string) {
	data := string(in)
	if data == " " || len(data) == 1 {
		// ping path
		if h.resource == "streaming" {
			out = append(out, `<iq type='get' from='server@`+h.domain+`/idle' to='@`+h.domain+`' id='OJN-0'><query xmlns='jabber:iq:version'/></iq>`)
		}
		return
	}
	// very small subset of the auth state machine similar to PluginAuth::DoAuth
	switch h.step {
	case 0:
		if has(data, "<stream:stream") {
			out = append(out, `<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' id='1' from='`+h.domain+`' version='1.0' xml:lang='en'>`+"<stream:features><mechanisms xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><mechanism>DIGEST-MD5</mechanism><mechanism>PLAIN</mechanism></mechanisms><register xmlns='http://violet.net/features/violet-register'/></stream:features>")
			h.step = 1
			return
		}
    case 1:
        if has(data, `<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>`) {
            if h.bypassAuth {
                out = append(out, `<success xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>`)
                h.step = 4
                return
            }
            // issue a random nonce per connection
            if h.nonce == "" {
                if h.nonceFactory != nil { h.nonce = h.nonceFactory() } else { h.nonce = genNonce() }
            }
            challenge := `realm="`+h.domain+`",nonce="`+h.nonce+`",qop="auth",charset=utf-8,algorithm=md5-sess`
            out = append(out, `<challenge xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>`+base64.StdEncoding.EncodeToString([]byte(challenge))+`</challenge>`)
            h.step = 2
            return
        }
        // SASL PLAIN fallback
        if reAuthPlain.MatchString(data) {
            if h.bypassAuth {
                out = append(out, `<success xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>`)
                h.step = 4
                return
            }
            m := reAuthPlain.FindStringSubmatch(data)
            if len(m) >= 2 {
                payload, _ := base64.StdEncoding.DecodeString(m[1])
                parts := splitNull([]byte(payload))
                if len(parts) >= 3 {
                    user := string(parts[1])
                    pass := string(parts[2])
                    if h.getPassword != nil {
                        if pw, ok := h.getPassword(user); ok && pw == pass {
                            out = append(out, `<success xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>`)
                            h.step = 4
                            return
                        }
                    }
                }
            }
            out = append(out, `<failure xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><not-authorized/></failure>`)
            h.step = 0
            return
        }
    case 2:
        if has(data, `<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>`) {
            if h.bypassAuth {
                out = append(out, `<success xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>`)
                h.step = 4
                return
            }
            // Decode and validate DIGEST-MD5
            m := reResponse.FindStringSubmatch(data)
            if len(m) >= 2 {
                payload, _ := base64.StdEncoding.DecodeString(m[1])
                directive := string(payload)
                user := capture(directive, `username="([^"]+)"`)
                if user == "" { user = capture(directive, `username='([^']+)'`) }
                if user != "" && h.getPassword != nil {
                    if pw, ok := h.getPassword(user); ok {
                        // expected digest-uri: xmpp/<domain>
                        digestURI := "xmpp/"+h.domain
                        if validateDigestMD5(directive, user, h.domain, pw, h.nonce, capture(directive, `cnonce="([^"]+)"`), capture(directive, `nc=([0-9a-fA-F]+)`), "auth", digestURI) {
                            out = append(out, `<success xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>`)
                            h.step = 4
                            h.authUser = user
                            return
                        }
                    }
                }
            }
            // invalid auth
            out = append(out, `<failure xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><not-authorized/></failure>`)
            h.step = 0
            return
        }
	case 4:
		if has(data, `<stream:stream`) {
			out = append(out, `<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' id='2' from='`+h.domain+`' version='1.0' xml:lang='en'>`+"<stream:features><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><required/></bind><unbind xmlns='urn:ietf:params:xml:ns:xmpp-bind'/><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></stream:features>")
			h.step = 0
			return
		}
	}
	// IQ handling subset: bind, session, sources
	if reIQ.MatchString(data) {
        h.tryIdentify(data)
        if has(data, "<bind") {
			h.resource = capture(data, `<resource>([^<]*)</resource>`)
            user := h.authUser
            if user == "" { user = "bunny" }
            jid := user+"@"+h.domain+"/"+h.resource
			out = append(out, iqReply(data, `<bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><jid>`+jid+`</jid></bind>`))
            if h.onRegistered != nil && h.authUser != "" { h.onRegistered(h.authUser, h.resource) }
			return
		}
		if has(data, `<session xmlns='urn:ietf:params:xml:ns:xmpp-session'/>`) {
			out = append(out, iqReply(data, `<session xmlns='urn:ietf:params:xml:ns:xmpp-session'/>`))
			return
		}
		if has(data, `<query xmlns="violet:iq:sources"><packet xmlns="violet:packet" format="1.0"/></query>`) {
			// reply empty for now
			out = append(out, iqReply(data, `<query xmlns='violet:iq:sources'><packet xmlns='violet:packet' format='1.0' ttl='604800'></packet></query>`))
			return
		}
	}
	// presence echo
	if rePresence.MatchString(data) {
        from := capture(data, `from='([^']*)'`)
        h.tryIdentify(data)
		id := capture(data, `id='([^']*)'`)
		out = append(out, `<presence from='`+from+`' to='`+from+`' id='`+id+`'/>`)
		return
	}
	// message button/ears logging not implemented here (plugins will handle later)
    if reMessage.MatchString(data) {
        // Button click: <button ...><clic>1</clic></button>
        if has(data, "<button") {
            clicks := capture(data, `<clic>([0-9]+)</clic>`)
            if clicks != "" && h.onButton != nil {
                if n, err := strconv.Atoi(clicks); err == nil {
                    id := h.bunnyID
                    if id == "" { id = h.authUser }
                    if id != "" { h.onButton(id, n) }
                }
            }
        }
        // Ears move: <ears ...><left>..</left><right>..</right></ears>
        if has(data, "<ears") {
            l := capture(data, `<left>([0-9]+)</left>`)
            r := capture(data, `<right>([0-9]+)</right>`)
            if l != "" && r != "" && h.onEars != nil {
                li, lerr := strconv.Atoi(l)
                ri, rerr := strconv.Atoi(r)
                if lerr == nil && rerr == nil {
                    id := h.bunnyID
                    if id == "" { id = h.authUser }
                    if id != "" { h.onEars(id, li, ri) }
                }
            }
        }
    }
	return
}

func has(s, sub string) bool { return regexp.MustCompile(regexp.QuoteMeta(sub)).FindStringIndex(s) != nil }

func capture(s, rx string) string {
	re := regexp.MustCompile(rx)
	m := re.FindStringSubmatch(s)
	if len(m) >= 2 { return m[1] }
	return ""
}

func iqReply(orig string, inner string) string {
	// naive id/from passthrough
	id := capture(orig, ` id='([^']*)'`)
	from := capture(orig, ` from='([^']*)'`)
	to := capture(orig, ` to='([^']*)'`)
	if to == "" { to = from }
	return `<iq type='result' from='`+to+`' to='`+from+`' id='`+id+`'>`+inner+`</iq>`
}

func (h *handler) tryIdentify(s string) {
    if h.bunnyID != "" { return }
    // Try to capture bunny id from from='BUNNY@domain/...'
    from := capture(s, `from='([^']*)'`)
    if from != "" {
        id := capture(from, `^([^@]+)@`)
        if id != "" {
            h.bunnyID = id
            if h.onIdentify != nil { h.onIdentify(id) }
        }
    }
}

func (h *handler) getID() string { return h.bunnyID }

func genNonce() string {
    b := make([]byte, 12)
    if _, err := rand.Read(b); err != nil {
        return "random"
    }
    return hex.EncodeToString(b)
}

func splitNull(b []byte) [][]byte {
    var parts [][]byte
    start := 0
    for i := 0; i < len(b); i++ {
        if b[i] == 0x00 {
            parts = append(parts, b[start:i])
            start = i+1
        }
    }
    parts = append(parts, b[start:])
    return parts
}
