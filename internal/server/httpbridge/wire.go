package httpbridge

import (
	"log/slog"

	"OpenJabNab/internal/api"
    plug "OpenJabNab/internal/plugin"
)

type Adapter struct {
	API *api.Manager
    Plugins *plug.Manager
}

func (a *Adapter) Process(rawURI string, req *Request) (string, []byte) {
    // First, let Go plugins attempt to handle legacy HTTP routes
    if a.Plugins != nil {
        pr := &plug.Request{URI: req.URI, RawURI: req.RawURI, Get: req.Get, Post: req.Post, RawPost: req.RawPost}
        handled := a.Plugins.HttpRequest(pr)
        if handled {
            return "text/plain", pr.Reply
        }
    }
    return a.API.Process(rawURI, req.URI, req.Get)
}

func New(addr string, logger *slog.Logger, apiMgr *api.Manager) *Server {
	return &Server{
		Addr:   addr,
		Logger: logger,
        API:    &Adapter{API: apiMgr},
	}
}
