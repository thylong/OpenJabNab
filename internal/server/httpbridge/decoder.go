package httpbridge

import (
	"bytes"
	"encoding/binary"
	"errors"
	"net/url"
	"strings"
)

var (
	errShort    = errors.New("frame too short")
	errBadSize  = errors.New("frame length mismatch")
	errMalformed = errors.New("malformed frame")
)

// Decode parses a single frame emitted by the PHP wrapper according to the legacy protocol.
// Frame layout:
// - 4 bytes little-endian total frame length (including these 4 bytes)
// - 1 byte type: 1=GET, 2=POST, 3=POSTRAW
// - raw headers (CRLF-separated) terminated by NUL (0x00)
// - raw URI terminated by NUL (0x00)
// - raw POST data (if any)
func Decode(frame []byte) (*Request, error) {
	if len(frame) < 5 {
		return nil, errShort
	}
	total := int(binary.LittleEndian.Uint32(frame[:4]))
	if total != len(frame) {
		return nil, errBadSize
	}
	typ := frame[4]
	payload := frame[5:]
	i := bytes.IndexByte(payload, 0)
	if i < 0 {
		return nil, errMalformed
	}
	rawHeaders := string(payload[:i])
	payload = payload[i+1:]
    var rawURI string
    var post []byte
    if typ == 1 { // GET: remainder is raw URI, no second NUL
        rawURI = string(payload)
        post = nil
    } else {
        j := bytes.IndexByte(payload, 0)
        if j < 0 {
            return nil, errMalformed
        }
        rawURI = string(payload[:j])
        post = payload[j+1:]
    }

	req := &Request{Headers: map[string]string{}, Get: map[string]string{}, Post: map[string]string{}}
	switch typ {
	case 1:
		req.Method = "GET"
	case 2:
		req.Method = "POST"
	case 3:
		req.Method = "POSTRAW"
	default:
		req.Method = "INVALID"
	}
	req.RawURI = rawURI
	// Parse headers
	if rawHeaders != "" {
		lines := strings.Split(rawHeaders, "\r\n")
		for _, ln := range lines {
			if ln == "" { continue }
			if k, v, ok := strings.Cut(ln, ":"); ok {
				req.Headers[strings.TrimSpace(k)] = strings.TrimSpace(v)
			}
		}
	}
	// Parse URI and query
	u, err := url.Parse(rawURI)
	if err == nil {
		req.URI = u.Path
		for k, vals := range u.Query() {
			if len(vals) > 0 {
				req.Get[k] = vals[0]
			}
		}
	} else {
		req.URI = rawURI
	}
	// Parse post body into key/value when not POSTRAW
	if len(post) > 0 {
		req.RawPost = append([]byte(nil), post...)
		if req.Method == "POST" {
			vals, _ := url.ParseQuery(string(post))
			for k, v := range vals {
				if len(v) > 0 {
					req.Post[k] = v[0]
				}
			}
		}
	}
	return req, nil
}
