package httpbridge

// Request represents the decoded request from the PHP wrapper TCP frame.
// It mirrors the essential fields used by routing and APIs.
type Request struct {
	Method  string
	RawURI  string
	URI     string
	Headers map[string]string
	Get     map[string]string
	Post    map[string]string
	RawPost []byte
}
