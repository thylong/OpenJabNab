# OpenJabNab PHP Web Interface Dockerfile  
FROM php:8.1-apache

# Install PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-install mysqli pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && pear channel-update pear.php.net \
    && pear install -f Mail \
    && pear install -f Mail_Mime \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Configure Apache to log to stdout/stderr instead of files
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log \
    && ln -sf /proc/self/fd/2 /var/log/apache2/error.log

# Create ojn_local directory and bootcode directory
RUN mkdir -p /var/www/html/ojn_local/bootcode

# Copy configuration files, setup script, and bootcode
COPY docker/web/config.php /tmp/config.php
COPY docker/web/setup.sh /usr/local/bin/setup.sh
COPY bootcode/bootcode.default /var/www/html/ojn_local/bootcode/bootcode.default
RUN chmod +x /usr/local/bin/setup.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Run setup script on container start
RUN echo '#!/bin/bash\n/usr/local/bin/setup.sh\nexec apache2-foreground "$@"' > /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Expose port 80
EXPOSE 80

# Use custom entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]