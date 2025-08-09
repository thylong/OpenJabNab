package httpbridge

import (
	"bufio"
	"encoding/binary"
	"fmt"
	"io"
	"log/slog"
	"net"
	"time"
)

type API interface {
	Process(rawURI string, req *Request) (contentType string, data []byte)
}

type Server struct {
	Addr    string // host:port
	Logger  *slog.Logger
	API     API
    OnListen func(addr string)
    Dump func(cat string, data []byte)
}

func (s *Server) ListenAndServe(stop <-chan struct{}) error {
	ln, err := net.Listen("tcp", s.Addr)
	if err != nil { return err }
    if s.OnListen != nil { s.OnListen(ln.Addr().String()) }
	defer ln.Close()
	done := make(chan struct{})
	go func() {
		<-stop
		_ = ln.Close()
		close(done)
	}()
	for {
		conn, err := ln.Accept()
		if err != nil {
			select {
			case <-done:
				return nil
			default:
				if ne, ok := err.(net.Error); ok && ne.Temporary() { continue }
				return err
			}
		}
		go s.handleConn(conn)
	}
}

func (s *Server) handleConn(c net.Conn) {
	defer c.Close()
	_ = c.SetDeadline(time.Now().Add(30 * time.Second))
	br := bufio.NewReader(c)
	// Read first 4 bytes for length
	lenBuf := make([]byte, 4)
	if _, err := io.ReadFull(br, lenBuf); err != nil {
		s.Logger.Warn("read len failed", slog.String("err", err.Error()))
		return
	}
	length := int(binary.LittleEndian.Uint32(lenBuf))
	if length < 5 || length > 10<<20 { // 10MB safety
		s.Logger.Warn("bad frame length", slog.Int("len", length))
		return
	}
    frame := make([]byte, length)
	copy(frame[:4], lenBuf)
	if _, err := io.ReadFull(br, frame[4:]); err != nil {
		s.Logger.Warn("read frame failed", slog.String("err", err.Error()))
		return
	}
    if s.Dump != nil { s.Dump("Api Call", frame) }
	req, err := Decode(frame)
	if err != nil {
		s.Logger.Warn("decode failed", slog.String("err", err.Error()))
		return
	}
	ct, data := s.API.Process(req.RawURI, req)
	if len(data) == 0 {
		data = []byte("")
	}
	// For now, we just write raw data as the legacy server did for API; plugins may write plain strings.
	// If content-type is significant, we could prepend HTTP headers; the PHP wrapper expects raw body.
    if _, err := c.Write(data); err != nil {
		s.Logger.Warn("write failed", slog.String("err", err.Error()))
		return
	}
    if s.Dump != nil { s.Dump("Api Answer", data) }
	_ = ct // reserved for future use
}

// EncodeResponse is a helper if we later need to mirror a framed response. Currently not used.
func EncodeResponse(b []byte) []byte {
	_ = fmt.Sprintf // silence unused for now
	return b
}
