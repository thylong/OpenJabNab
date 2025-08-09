package httpbridge

import (
	"log/slog"

	"OpenJabNab/internal/api"
)

type Adapter struct {
	API *api.Manager
}

func (a *Adapter) Process(rawURI string, req *Request) (string, []byte) {
    return a.API.Process(rawURI, req.URI, req.Get)
}

func New(addr string, logger *slog.Logger, apiMgr *api.Manager) *Server {
	return &Server{
		Addr:   addr,
		Logger: logger,
		API:    &Adapter{API: apiMgr},
	}
}
