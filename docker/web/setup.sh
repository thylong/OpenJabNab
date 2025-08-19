#!/bin/bash
# OpenJabNab Web Setup Script for Docker

# Remove broken symlink if it exists
if [ -L "/var/www/html/include/config.php" ]; then
    rm -f "/var/www/html/include/config.php"
fi

# Copy Docker configuration file
cp /tmp/config.php /var/www/html/include/config.php

# Fix session_start issue in common.php (line 84)
sed -i '84s/^session_start();$/if (session_status() === PHP_SESSION_NONE) { session_start(); }/' /var/www/html/include/common.php

# Set proper permissions
chown www-data:www-data /var/www/html/include/config.php
chmod 644 /var/www/html/include/config.php

# Create ojn_local directory if it doesn't exist
mkdir -p /var/www/html/ojn_local
chown -R www-data:www-data /var/www/html/ojn_local

echo "OpenJabNab web configuration setup complete"
echo "Config file created with constants: ROOT_SITE, APC_PREFIX, DB settings"