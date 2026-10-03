#!/bin/bash
set -e

# Get PORT from environment (Render sets this)
PORT="${PORT:-80}"

echo "=========================================="
echo "Starting Vantablack Backend on port $PORT"
echo "=========================================="

# Update Apache port configuration
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf

# Update VirtualHost port
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Configure document root
cat > /etc/apache2/sites-available/000-default.conf << EOF
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

echo "Apache configured to listen on port $PORT"
echo "Starting Apache..."

# Start Apache in foreground
exec apache2-foreground