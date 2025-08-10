package nhttp

import (
    "io"
    "log/slog"
    "net/http"
    "strings"
    "path/filepath"
    "os"

    "OpenJabNab/internal/api"
    plug "OpenJabNab/internal/plugin"
)

type Server struct {
	Addr    string
	Logger  *slog.Logger
	API     *api.Manager
	Plugins *plug.Manager
    // Optional static root to serve broadcast files
    StaticRoot string
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
    // Serve broadcast under static root if configured
    if s.StaticRoot != "" && strings.HasPrefix(uri, "/broadcast/") {
        clean := filepath.Clean(uri)
        full := filepath.Join(s.StaticRoot, filepath.FromSlash(clean))
        root := filepath.Clean(s.StaticRoot)
        if !strings.HasPrefix(full, root+string(os.PathSeparator)) {
            w.WriteHeader(http.StatusForbidden)
            _, _ = w.Write([]byte("Forbidden"))
            return
        }
        http.ServeFile(w, r, full)
        return
    }
    // Run plugin HTTP pipeline for all URIs before API routing (parity with httpbridge)
    if s.Plugins != nil {
        preq := &plug.Request{URI: uri, RawURI: r.URL.RequestURI(), Get: map[string]string{}, Post: map[string]string{}}
        for k, v := range q { if len(v)>0 { preq.Get[k] = v[0] } }
        if r.Method == http.MethodPost {
            // Read raw body for POST; best-effort parsing of form values
            if b, err := io.ReadAll(r.Body); err == nil { preq.RawPost = b }
            _ = r.ParseForm()
            for k := range r.PostForm { if vals := r.PostForm[k]; len(vals)>0 { preq.Post[k] = vals[0] } }
        }
        if s.Plugins.HttpRequest(preq) {
            w.Header().Set("Content-Type", "text/plain; charset=utf-8")
            _, _ = w.Write(preq.Reply)
            return
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
