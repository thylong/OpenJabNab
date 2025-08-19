#!/bin/bash
# OpenJabNab C++ Server Setup Script for Docker

echo "Setting up OpenJabNab C++ server configuration..."

# Update openjabnab.ini with correct domain settings
if [ ! -z "${OJN_DOMAIN}" ] && [ "${OJN_DOMAIN}" != "localhost" ]; then
    echo "Updating OpenJabNab server configuration for domain: ${OJN_DOMAIN}"
    
    # Update server hostnames in openjabnab.ini
    sed -i "s/PingServer = nabaztag\.yourdomain\.com/PingServer = ${OJN_DOMAIN}/" /app/bin/openjabnab.ini
    sed -i "s/BroadServer = nabaztag\.yourdomain\.com/BroadServer = ${OJN_DOMAIN}/" /app/bin/openjabnab.ini  
    sed -i "s/XmppServer = nabaztag\.yourdomain\.com/XmppServer = ${OJN_DOMAIN}/" /app/bin/openjabnab.ini
    
    echo "OpenJabNab server configured for domain: ${OJN_DOMAIN}"
else
    echo "Using default localhost configuration"
    # Revert to localhost for local development
    sed -i "s/PingServer = nabaztag\.yourdomain\.com/PingServer = localhost/" /app/bin/openjabnab.ini
    sed -i "s/BroadServer = nabaztag\.yourdomain\.com/BroadServer = localhost/" /app/bin/openjabnab.ini  
    sed -i "s/XmppServer = nabaztag\.yourdomain\.com/XmppServer = localhost/" /app/bin/openjabnab.ini
fi

# Update ports if provided
if [ ! -z "${OJN_HTTP_PORT}" ]; then
    sed -i "s/ListeningHttpPort = 8080/ListeningHttpPort = ${OJN_HTTP_PORT}/" /app/bin/openjabnab.ini
fi

if [ ! -z "${OJN_XMPP_PORT}" ]; then
    sed -i "s/ListeningXmppPort = 5222/ListeningXmppPort = ${OJN_XMPP_PORT}/" /app/bin/openjabnab.ini
fi

echo "OpenJabNab C++ server configuration setup complete"
echo "Domain configured: ${OJN_DOMAIN:-localhost}"
echo "HTTP Port: ${OJN_HTTP_PORT:-8080}"
echo "XMPP Port: ${OJN_XMPP_PORT:-5222}"

# Start the OpenJabNab server
echo "Starting OpenJabNab server..."
exec ./openjabnab --config-dir /app/bin