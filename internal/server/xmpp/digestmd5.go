package xmpp

import (
	"crypto/md5"
	"encoding/hex"
	"fmt"
	"strings"
)

// minimal DIGEST-MD5 response validation. This is a simplified version and assumes qop=auth.
func validateDigestMD5(response, username, realm, password, nonce, cnonce, nc, qop, digestURI string) bool {
	// response is of the form: username=...,realm=...,nonce=...,cnonce=...,nc=...,qop=...,digest-uri=...,response=...,charset=utf-8
	fields := parseDirectives(response)
	if fields["username"] != username { return false }
	if fields["nonce"] != nonce { return false }
	if fields["cnonce"] != cnonce { return false }
	if fields["nc"] != nc { return false }
	if fields["qop"] != qop { return false }
	if fields["digest-uri"] != digestURI { return false }

	ha1 := md5sum(fmt.Sprintf("%s:%s:%s", username, realm, password))
	ha1ses := md5sum(fmt.Sprintf("%s:%s:%s", ha1, nonce, cnonce))
	ha2 := md5sum("AUTHENTICATE:" + digestURI)
	expected := md5sum(fmt.Sprintf("%s:%s:%s:%s:%s:%s", ha1ses, nonce, nc, cnonce, qop, ha2))
	return strings.EqualFold(fields["response"], expected)
}

func md5sum(s string) string {
	sum := md5.Sum([]byte(s))
	return hex.EncodeToString(sum[:])
}

func parseDirectives(s string) map[string]string {
	m := make(map[string]string)
	for _, p := range strings.Split(s, ",") {
		p = strings.TrimSpace(p)
		if p == "" { continue }
		kv := strings.SplitN(p, "=", 2)
		if len(kv) != 2 { continue }
		v := strings.Trim(kv[1], `"`)
		m[kv[0]] = v
	}
	return m
}
