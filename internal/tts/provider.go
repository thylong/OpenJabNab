package tts

import "context"

type Voice struct {
	ID       string
	Language string
	Name     string
	Gender   string
}

type Provider interface {
	ListVoices(ctx context.Context) ([]Voice, error)
	Synthesize(ctx context.Context, text string, voiceID string) (audio []byte, codec string, err error)
}

var registry = map[string]Provider{}

func Register(name string, p Provider) { registry[name] = p }

func Get(name string) Provider {
	if p, ok := registry[name]; ok { return p }
	if p, ok := registry["mock"]; ok { return p }
	return mockProvider{}
}
