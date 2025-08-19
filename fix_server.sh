#!/bin/bash
set -e

echo "Building fixed openjabnab binary..."
docker run --rm -v $(pwd):/workspace -w /workspace/server ubuntu:20.04 bash -c "
    export DEBIAN_FRONTEND=noninteractive
    apt-get update && apt-get install -y build-essential cmake qt5-default libqt5network5 libqt5sql5 libqt5sql5-mysql qttools5-dev
    mkdir -p build && cd build
    cmake ..
    make openjabnab -j\$(nproc)
"

echo "Copying fixed binary to running container..."
docker cp server/build/bin/openjabnab openjabnab_server:/app/bin/openjabnab_fixed

echo "Restarting with fixed binary..."
docker-compose exec openjabnab bash -c "cd /app/bin && ./openjabnab_fixed --config-dir /app/bin" &