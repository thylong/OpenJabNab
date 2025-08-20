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

# Update Apache virtual host configuration with correct domain
if [ ! -z "${OJN_DOMAIN}" ] && [ "${OJN_DOMAIN}" != "localhost" ]; then
    echo "Updating Apache configuration for domain: ${OJN_DOMAIN}"
    # Update ServerName in Apache configuration
    sed -i "s/ServerName nabaztag\.yourdomain\.com/ServerName ${OJN_DOMAIN}/" /etc/apache2/sites-available/000-default.conf
    # Update ServerAlias to include the domain
    sed -i "s/ServerAlias localhost \*\.nabaztag\.yourdomain\.com/ServerAlias localhost *.${OJN_DOMAIN} ${OJN_DOMAIN}/" /etc/apache2/sites-available/000-default.conf
    echo "Apache configured for domain: ${OJN_DOMAIN}"
else
    echo "Using default localhost configuration"
fi

# Configure Apache logging to stdout/stderr
echo "Configuring Apache logging to stdout/stderr"
sed -i 's|ErrorLog ${APACHE_LOG_DIR}/openjabnab_error.log|ErrorLog /proc/self/fd/2|' /etc/apache2/sites-available/000-default.conf
sed -i 's|CustomLog ${APACHE_LOG_DIR}/openjabnab_access.log combined|CustomLog /proc/self/fd/1 combined|' /etc/apache2/sites-available/000-default.conf
echo "Apache logging configured for Docker"

echo "OpenJabNab web configuration setup complete"
echo "Config file created with constants: ROOT_SITE, APC_PREFIX, DB settings"
echo "Domain configured: ${OJN_DOMAIN:-localhost}"