package api

import (
	"OpenJabNab/internal/account"
	"OpenJabNab/internal/bunny"
)

type DefaultPluginAPI struct{}

type DefaultBunnyAPI struct{ B *bunny.Manager }

type DefaultZtampAPI struct{}

type DefaultAccountsAPI struct{ A *account.Manager }

func (d DefaultPluginAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
	return []byte(`<ok/>`), nil
}

func (d DefaultBunnyAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
	// Minimal: expose connected count
	if request == "stats" {
		return []byte(`<connected>` + itoa(d.B.ConnectedCount()) + `</connected>`), nil
	}
	return []byte(`<ok/>`), nil
}

func (d DefaultBunnyAPI) ProcessViolet(request string, get map[string]string) ([]byte, error) {
	return []byte(`<message>PONG</message><comment></comment>`), nil
}

func (d DefaultZtampAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
	return []byte(`<ok/>`), nil
}

func (d DefaultAccountsAPI) Process(token string, request string, get map[string]string) ([]byte, error) {
	if !d.A.HasAccess(token) { return []byte(`<error>Access denied</error>`), nil }
	return []byte(`<ok/>`), nil
}
