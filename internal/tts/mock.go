package tts

import "context"

type mockProvider struct{}

func (mockProvider) ListVoices(ctx context.Context) ([]Voice, error) {
	return []Voice{{ID: "en-US-Standard-A", Language: "en-US", Name: "Standard A", Gender: "F"}}, nil
}

func (mockProvider) Synthesize(ctx context.Context, text string, voiceID string) ([]byte, string, error) {
	// return tiny silent mp3 header-like bytes or placeholder; for tests use raw bytes
	return []byte{0x49, 0x44, 0x33}, "mp3", nil
}
