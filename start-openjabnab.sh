#!/bin/bash
# OpenJabNab Docker Compose Startup Script

echo "🐰 Starting OpenJabNab Docker Environment..."

# Check if Docker and Docker Compose are available
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed or not in PATH"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed or not in PATH"  
    exit 1
fi

# Create ojn_local bootcode directory if it doesn't exist
mkdir -p docker/web/ojn_local/bootcode

# Copy bootcode files to web directory
if [ -d "bootcode" ]; then
    echo "📁 Copying bootcode files..."
    cp -r bootcode/* docker/web/ojn_local/bootcode/
    # Create default bootcode file if it doesn't exist
    if [ ! -f "docker/web/ojn_local/bootcode/bootcode.default" ]; then
        echo "Creating default bootcode file..."
        touch docker/web/ojn_local/bootcode/bootcode.default
    fi
else
    echo "⚠️  Bootcode directory not found, creating empty bootcode"
    touch docker/web/ojn_local/bootcode/bootcode.default
fi

# Start the services
echo "🚀 Starting Docker containers..."
docker-compose up -d

# Wait for services to be healthy
echo "⏳ Waiting for services to start..."
sleep 10

# Check service status
echo "📊 Checking service status..."
docker-compose ps

# Display connection information
echo ""
echo "🎉 OpenJabNab is starting up!"
echo ""
echo "📱 Service URLs:"
echo "   Web Interface:  http://localhost"
echo "   Admin Panel:    http://localhost/ojn_admin"
echo "   phpMyAdmin:     http://localhost:8081"
echo "   C++ Server:     localhost:8080"
echo "   XMPP Server:    localhost:5222"
echo ""
echo "🔧 For Nabaztag setup:"
echo "   1. Put your Nabaztag in adhoc mode (blue light)"
echo "   2. Connect to its WiFi network (NabaztagXX)"
echo "   3. Browse to http://192.168.0.1/"
echo "   4. Configure WiFi settings if needed"
echo "   5. Set server to: $(hostname -I | awk '{print $1}' || echo 'YOUR_IP')/vl"
echo "   6. Or use: localhost/vl if running on the same machine"
echo ""
echo "📋 Useful commands:"
echo "   View logs:     docker-compose logs -f"
echo "   Stop services: docker-compose down"
echo "   Restart:       docker-compose restart"
echo ""
echo "✅ Setup complete! Check the logs if any service fails to start."