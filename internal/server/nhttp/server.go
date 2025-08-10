package nhttp

import (
    "log/slog"
	"net/http"
	"strings"

	"OpenJabNab/internal/api"
	plug "OpenJabNab/internal/plugin"
)

type Server struct {
	Addr    string
	Logger  *slog.Logger
	API     *api.Manager
	Plugins *plug.Manager
}

func (s *Server) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	uri := r.URL.Path
	q := r.URL.Query()
    s.Logger.Info("http", slog.String("method", r.Method), slog.String("uri", uri), slog.String("ua", r.Header.Get("User-Agent")), slog.String("remote", r.RemoteAddr))
    // Bootcode mapping (serve from mounted http-wrapper volume)
    if uri == "/vl/bc.jsp" || uri == "/bc.jsp" {
        http.ServeFile(w, r, "/app/http-wrapper/ojn_local/bootcode/bootcode.default")
		return
	}
	// locate via plugin or API
	if strings.HasPrefix(uri, "/vl/locate.jsp") {
		if s.Plugins != nil {
			req := &plug.Request{URI: uri, RawURI: r.URL.RequestURI(), Get: map[string]string{}}
			for k, v := range q { if len(v)>0 { req.Get[k] = v[0] } }
			if s.Plugins.HttpRequest(req) {
				w.Header().Set("Content-Type", "text/plain; charset=utf-8")
				_, _ = w.Write(req.Reply)
				return
			}
		}
	}
	// API routes
	if strings.HasPrefix(uri, "/ojn_api/") || strings.HasPrefix(uri, "/ojn/FR/api") {
		get := map[string]string{}
		for k, v := range q { if len(v)>0 { get[k] = v[0] } }
		_, data := s.API.Process(r.URL.RequestURI(), uri, get)
		w.Header().Set("Content-Type", "text/xml; charset=utf-8")
		_, _ = w.Write(data)
		return
	}
	w.WriteHeader(http.StatusNotFound)
	_, _ = w.Write([]byte("Not Found"))
}

func (s *Server) Start() error {
	mux := http.NewServeMux()
	mux.Handle("/", s)
    addr := s.Addr
    if addr == "" { addr = ":8081" }
    return http.ListenAndServe(addr, mux)
}
