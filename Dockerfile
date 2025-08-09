# Multi-stage build to produce a tiny, non-root image

# 1) Build stage
FROM golang:1.22-bookworm AS builder

ARG TARGETOS
ARG TARGETARCH
WORKDIR /src

# Cache modules first
COPY go.mod go.sum ./
RUN --mount=type=cache,target=/go/pkg/mod go mod download

# Copy sources
COPY . .

# Build static binary (no CGO)
ENV CGO_ENABLED=0
RUN --mount=type=cache,target=/root/.cache/go-build \
    GOOS=${TARGETOS:-linux} GOARCH=${TARGETARCH:-amd64} \
    go build -trimpath -ldflags="-s -w" -o /out/openjabnab ./cmd/openjabnab

# 2) Runtime stage
FROM gcr.io/distroless/static:nonroot

# Working directory for logs or runtime files if needed
WORKDIR /app

# Note: distroless has no shell; ensure log path is writable by nonroot via volume mount

# Config path: mount your openjabnab.ini at /config/openjabnab.ini
ENV OJN_CONFIG=/config/openjabnab.ini

# Copy binary
COPY --from=builder /out/openjabnab /openjabnab

# Non-root runtime
USER nonroot:nonroot

# Expose default ports (override via config if needed)
EXPOSE 8080 5222

ENTRYPOINT ["/openjabnab"]
