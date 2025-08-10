package api_test

import (
	"bufio"
	"bytes"
	"crypto/sha1"
	"encoding/base64"
	"encoding/binary"
	"encoding/hex"
	"net"
	"os"
	"path/filepath"
	"strings"
	"testing"
	"time"

	"log/slog"

	api "OpenJabNab/internal/api"
	cfgpkg "OpenJabNab/internal/config"
	plugman "OpenJabNab/internal/plugin"
	plugtts "OpenJabNab/internal/plugins/tts"
	httpb "OpenJabNab/internal/server/httpbridge"
	xmpp "OpenJabNab/internal/server/xmpp"
)

func frameGETtts(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestTTS_Speak_WritesFileAndSendsPacket(t *testing.T) {
	// Start XMPP server
	stop := make(chan struct{})
	var addr string
	xs := &xmpp.Server{Addr: "127.0.0.1:0", Domain: "example.com", Logger: slog.Default(), OnListen: func(a string){ addr = a }}
	xs.GetPassword = func(u string) (string, bool) { if u=="user" { return "pass", true }; return "", false }
	go func(){ _ = xs.ListenAndServe(stop) }()
	defer close(stop)
	deadline := time.Now().Add(2*time.Second)
	for addr == "" && time.Now().Before(deadline) { time.Sleep(10*time.Millisecond) }
	if addr == "" { t.Fatal("xmpp server did not start") }

	// Bunny client
	c, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	defer c.Close()
	bw := bufio.NewWriter(c)
	br := bufio.NewReader(c)
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='example.com' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	buf := make([]byte, 4096)
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// SASL PLAIN
	payload := []byte{0x00}
	payload = append(payload, []byte("user")...)
	payload = append(payload, 0x00)
	payload = append(payload, []byte("pass")...)
	_, _ = bw.WriteString("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" + base64.StdEncoding.EncodeToString(payload) + "</auth>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// second stream
	_, _ = bw.WriteString("<?xml version='1.0'?><stream:stream to='example.com' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }
	// bind resource
	_, _ = bw.WriteString("<iq type='set' id='1' to='user@example.com'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>streaming</resource></bind></iq>")
	_ = bw.Flush()
	c.SetReadDeadline(time.Now().Add(1*time.Second))
	if _, err := br.Read(buf); err != nil { t.Fatal(err) }

	// Setup TTS plugin with mock provider and temp broadcast root
	dir := t.TempDir()
	cfg := &cfgpkg.Config{RealHttpRoot: dir, TTS: "mock"}
	pm := plugman.NewManager()
	pl := plugtts.New(cfg)
	pl.SetPacketSender(xs.SendPacket)
	pm.Register(pl)

	mgr := &api.Manager{Logger: slog.Default(), PluginsDir: dir}
	mgr.PluginProcess = pm.ProcessPluginApi

	hb := httpb.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	hb.OnListen = func(a string){ bound <- a }
	if adapter, ok := hb.API.(*httpb.Adapter); ok { adapter.Plugins = pm }
	st := make(chan struct{})
	go func(){ _ = hb.ListenAndServe(st) }()
	defer close(st)
	httpAddr := <-bound

	// Speak
	conn, err := net.Dial("tcp", httpAddr)
	if err != nil { t.Fatal(err) }
	defer conn.Close()
	text := "hello"
	_, _ = conn.Write(frameGETtts("/ojn_api/plugin/tts/speak?bunny=user&text=" + text))
	hbuf := make([]byte, 4096)
	_ = conn.SetReadDeadline(time.Now().Add(1 * time.Second))
	n, err := conn.Read(hbuf)
	if err != nil { t.Fatal(err) }
	if !bytes.Contains(hbuf[:n], []byte("<ok/>")) { t.Fatalf("unexpected tts response: %s", string(hbuf[:n])) }

	// Expect MU broadcast/tts/... in XMPP packet
	c.SetReadDeadline(time.Now().Add(2*time.Second))
	n, err = br.Read(buf)
	if err != nil { t.Fatal(err) }
	resp := string(buf[:n])
    pktStart := strings.Index(resp, "<packet ")
    if pktStart < 0 { t.Fatalf("no packet: %s", resp) }
    gtRel := strings.Index(resp[pktStart:], ">")
    if gtRel < 0 { t.Fatalf("malformed packet tag: %s", resp[pktStart:]) }
    contentStart := pktStart + gtRel + 1
    endRel := strings.Index(resp[pktStart:], "</packet>")
    if endRel < 0 { t.Fatalf("no closing packet tag: %s", resp[pktStart:]) }
    contentEnd := pktStart + endRel
    b64 := resp[contentStart:contentEnd]
    raw, err := base64.StdEncoding.DecodeString(strings.TrimSpace(b64))
	if err != nil { t.Fatal(err) }
	if !bytes.Contains(raw, []byte("MU broadcast/tts/")) { t.Fatalf("missing MU broadcast path in packet: %q", string(raw)) }

	// File exists under RealHttpRoot/broadcast/tts/<voice>/<hash>.mp3
	voice := "en-US-Standard-A"
	sum := sha1.Sum([]byte("mock:" + voice + ":mp3:" + text))
	key := hex.EncodeToString(sum[:])
	full := filepath.Join(dir, "broadcast", "tts", voice, key+".mp3")
	if _, err := os.Stat(full); err != nil { t.Fatalf("expected output file: %s", full) }
}
