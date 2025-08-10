package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"testing"
    "strings"

	api "OpenJabNab/internal/api"
	"OpenJabNab/internal/account"
	"OpenJabNab/internal/server/httpbridge"
)

func frame(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestAccountsLoginValidateLogout(t *testing.T) {
	acc := account.NewManager()
	acc.AddUser("user", "pass")
	mgr := &api.Manager{Logger: slog.Default(), Accounts: api.DefaultAccountsAPI{A: acc}}
	s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
	stop := make(chan struct{})
	bound := make(chan string, 1)
	s.OnListen = func(a string){ bound <- a }
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
	addr := <-bound

	// login
	conn, err := net.Dial("tcp", addr)
	if err != nil { t.Fatal(err) }
	defer conn.Close()
	if _, err := conn.Write(frame("/ojn_api/accounts/login?user=user&pass=pass")); err != nil { t.Fatal(err) }
	buf := make([]byte, 1024)
	n, err := conn.Read(buf)
	if err != nil { t.Fatal(err) }
	got := string(buf[:n])
	if !bytes.Contains(buf[:n], []byte("<token>")) { t.Fatalf("expected token, got: %s", got) }
	token := got[strings.Index(got, "<token>")+7:strings.Index(got, "</token>")]

	// validate
	conn2, _ := net.Dial("tcp", addr)
	defer conn2.Close()
	if _, err := conn2.Write(frame("/ojn_api/accounts/validate?token=" + token)); err != nil { t.Fatal(err) }
	n, err = conn2.Read(buf)
	if err != nil { t.Fatal(err) }
	if !bytes.Contains(buf[:n], []byte("<valid>true</valid>")) { t.Fatalf("unexpected validate response: %s", string(buf[:n])) }

	// logout
	conn3, _ := net.Dial("tcp", addr)
	defer conn3.Close()
	if _, err := conn3.Write(frame("/ojn_api/accounts/logout?token=" + token)); err != nil { t.Fatal(err) }
	n, err = conn3.Read(buf)
	if err != nil { t.Fatal(err) }
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("unexpected logout response: %s", string(buf[:n])) }
}
