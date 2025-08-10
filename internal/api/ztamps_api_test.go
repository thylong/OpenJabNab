package api_test

import (
	"bytes"
	"encoding/binary"
	"log/slog"
	"net"
	"testing"

	api "OpenJabNab/internal/api"
	"OpenJabNab/internal/server/httpbridge"
	"OpenJabNab/internal/ztamp"
)

func frameZ(uri string) []byte {
	payload := []byte{1}
	payload = append(payload, []byte("Host: test\r\n")...)
	payload = append(payload, 0)
	payload = append(payload, []byte(uri)...)
	buf := make([]byte, 4)
	binary.LittleEndian.PutUint32(buf, uint32(len(payload)+4))
	return append(buf, payload...)
}

func TestZtampsAddAssignList(t *testing.T) {
	z := ztamp.NewManager()
	mgr := &api.Manager{Logger: slog.Default(), Ztamps: api.DefaultZtampAPI{
		ZCount: z.Count,
		List:   z.List,
		Add:    z.Add,
		Remove: z.Remove,
		Assign: z.Assign,
		Unassign: z.Unassign,
		AssignedTo: z.AssignedTo,
	}}
	s := httpbridge.New("127.0.0.1:0", slog.Default(), mgr)
	bound := make(chan string, 1)
	s.OnListen = func(a string){ bound <- a }
	stop := make(chan struct{})
	go func(){ _ = s.ListenAndServe(stop) }()
	defer close(stop)
	addr := <-bound

	conn, _ := net.Dial("tcp", addr)
	defer conn.Close()

	// add
	_, _ = conn.Write(frameZ("/ojn_api/ztamps/add?id=z1"))
	buf := make([]byte, 1024)
	n, _ := conn.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("add unexpected: %s", string(buf[:n])) }

	// list
	conn2, _ := net.Dial("tcp", addr)
	defer conn2.Close()
	_, _ = conn2.Write(frameZ("/ojn_api/ztamps/list"))
	n, _ = conn2.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<item>z1</item>")) { t.Fatalf("list unexpected: %s", string(buf[:n])) }

	// assign
	conn3, _ := net.Dial("tcp", addr)
	defer conn3.Close()
	_, _ = conn3.Write(frameZ("/ojn_api/ztamp/z1/assign?bunny=b1"))
	n, _ = conn3.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<ok/>")) { t.Fatalf("assign unexpected: %s", string(buf[:n])) }

	// assigned
	conn4, _ := net.Dial("tcp", addr)
	defer conn4.Close()
	_, _ = conn4.Write(frameZ("/ojn_api/ztamp/z1/assigned"))
	n, _ = conn4.Read(buf)
	if !bytes.Contains(buf[:n], []byte("<bunny>b1</bunny>")) { t.Fatalf("assigned unexpected: %s", string(buf[:n])) }
}
