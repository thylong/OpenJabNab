# OpenJabNab C++ Server Dockerfile
FROM ubuntu:20.04

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    cmake \
    git \
    qt5-default \
    libqt5core5a \
    libqt5network5 \
    libqt5sql5 \
    libqt5sql5-mysql \
    qttools5-dev-tools \
    qttools5-dev \
    qt5-qmake \
    mysql-client \
    curl \
    libttspico-utils \
    ffmpeg \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy server source code
COPY server/ /app/server/

# Create build directory and build the project
WORKDIR /app/server
RUN mkdir -p build && cd build && \
    cmake .. && \
    make -j$(nproc)

# Create runtime directory structure
WORKDIR /app
RUN mkdir -p bin && \
    mkdir -p conf && \
    mkdir -p http-wrapper

# Copy built binaries and libraries
RUN cp -r /app/server/build/bin/* /app/bin/ || true

# Copy configuration files
COPY server/conf/ /app/conf/

# Create ojn_local directory structure for bootcode
RUN mkdir -p /app/bin/ojn_local/bootcode

# Copy bootcode files
COPY bootcode/ /app/bin/ojn_local/bootcode/

# Copy and set up the setup script
COPY docker/server/setup.sh /app/bin/setup.sh
RUN chmod +x /app/bin/setup.sh

# Set execute permissions
RUN chmod +x /app/bin/* || true

# Expose ports
EXPOSE 8080 5222

# Health check using curl to test HTTP port
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8080/ >/dev/null 2>&1 || exit 1

# Start the OpenJabNab server using setup script
WORKDIR /app/bin
CMD ["./setup.sh"]