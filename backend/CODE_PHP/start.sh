#!/bin/bash
set -e

# Get PORT from environment (Render sets this)
PORT="${PORT:-80}"

echo "=========================================="
echo "Starting Vantablack Backend on port $PORT"
echo "=========================================="

# Verify public/index.php exists
if [ ! -f /var/www/html/public/index.php ]; then
    echo "ERROR: /var/www/html/public/index.php not found!"
    echo "Listing /var/www/html contents:"
    ls -la /var/www/html/
    exit 1
fi

echo "Found public/index.php"

# Rewrite ports.conf completely (avoid sed issues)
cat > /etc/apache2/ports.conf << EOF
Listen ${PORT}
EOF

# Rewrite Apache vhost completely
cat > /etc/apache2/sites-available/000-default.conf << EOF
<VirtualHost *:${PORT}>
    ServerName localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>

    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Enable the site
a2ensite 000-default.conf > /dev/null 2>&1 || true
a2dissite default-ssl.conf > /dev/null 2>&1 || true

echo "Apache vhost configured:"
echo "  DocumentRoot: /var/www/html/public"
echo "  Port: ${PORT}"
echo ""
echo "Verifying configuration..."
apache2ctl configtest

echo ""
echo "Starting Apache..."
exec apache2-foreground