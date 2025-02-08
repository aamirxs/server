#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration variables
PANEL_DOMAIN=""
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 12)
PANEL_DB_PASSWORD=$(openssl rand -base64 12)
PANEL_ADMIN_PASSWORD=$(openssl rand -base64 12)
GITHUB_REPO="https://github.com/aamirxs/server.git"

echo -e "${GREEN}Starting Server Panel Installation...${NC}"

# Function to get server IP
get_server_ip() {
    SERVER_IP=$(curl -s ifconfig.me)
    if [ -z "$SERVER_IP" ]; then
        SERVER_IP=$(hostname -I | awk '{print $1}')
    fi
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root${NC}"
    exit 1
fi

# Get the server IP
get_server_ip

# Update system
echo -e "${BLUE}Updating system...${NC}"
apt-get update
apt-get upgrade -y

# Install required packages
echo -e "${BLUE}Installing required packages...${NC}"
apt-get install -y apache2 php php-mysql php-zip php-mbstring php-curl \
    mysql-server git unzip wget nodejs npm certbot python3-certbot-apache \
    php-cli php-json php-common php-xml php-gd php-opcache php-mysql php-zip \
    php-curl php-mbstring php-intl php-imagick php-redis php-sqlite3

# Secure MySQL installation
echo -e "${BLUE}Configuring MySQL...${NC}"
mysql --user=root <<_EOF_
ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
_EOF_

# Create database and user
echo -e "${BLUE}Creating database...${NC}"
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<EOF
CREATE DATABASE IF NOT EXISTS server_panel;
CREATE USER IF NOT EXISTS 'panel_user'@'localhost' IDENTIFIED BY '${PANEL_DB_PASSWORD}';
GRANT ALL PRIVILEGES ON server_panel.* TO 'panel_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Configure Apache
echo -e "${BLUE}Configuring Apache...${NC}"
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Create Apache virtual host with IP instead of domain
PANEL_URL="http://${SERVER_IP}"

cat > /etc/apache2/sites-available/server-panel.conf <<EOF
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/server-panel
    
    <Directory /var/www/server-panel>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/panel-error.log
    CustomLog \${APACHE_LOG_DIR}/panel-access.log combined
</VirtualHost>
EOF

a2ensite server-panel.conf
systemctl restart apache2

# Create project directory and set permissions
echo -e "${BLUE}Setting up project directory...${NC}"
mkdir -p /var/www/server-panel
cd /var/www/server-panel

# Clone the project
git clone ${GITHUB_REPO} .

# Create configuration file
cat > /var/www/server-panel/config/database.php <<EOF
<?php
return [
    'host' => 'localhost',
    'database' => 'server_panel',
    'username' => 'panel_user',
    'password' => '${PANEL_DB_PASSWORD}',
];
EOF

# Import database schema
mysql -u panel_user -p"${PANEL_DB_PASSWORD}" server_panel < database/schema.sql

# Create initial admin user
mysql -u panel_user -p"${PANEL_DB_PASSWORD}" server_panel <<EOF
INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@${PANEL_DOMAIN}', '$(echo -n "${PANEL_ADMIN_PASSWORD}" | php -r 'echo password_hash(fgets(STDIN), PASSWORD_DEFAULT);')', 'admin');
EOF

# Set proper permissions
chown -R www-data:www-data /var/www/server-panel
chmod -R 755 /var/www/server-panel
find /var/www/server-panel -type f -exec chmod 644 {} \;
find /var/www/server-panel -type d -exec chmod 755 {} \;

# Create necessary directories with proper permissions
mkdir -p /var/www/server-panel/storage/logs
mkdir -p /var/www/server-panel/storage/backups
mkdir -p /var/www/server-panel/storage/temp
chown -R www-data:www-data /var/www/server-panel/storage
chmod -R 775 /var/www/server-panel/storage

# Setup cron jobs
echo "*/5 * * * * www-data php /var/www/server-panel/cron/monitor.php" > /etc/cron.d/server-panel
echo "0 2 * * * www-data php /var/www/server-panel/cron/backup.php" >> /etc/cron.d/server-panel
chmod 644 /etc/cron.d/server-panel

# Final setup steps
systemctl restart apache2
systemctl restart mysql

# Save credentials to a secure file
cat > /root/.server-panel-credentials <<EOF
Server Panel Credentials
=======================
URL: ${PANEL_URL}
Admin Username: admin
Admin Password: ${PANEL_ADMIN_PASSWORD}

Database Credentials
==================
Root Password: ${MYSQL_ROOT_PASSWORD}
Panel DB User: panel_user
Panel DB Password: ${PANEL_DB_PASSWORD}

Please save these credentials securely and delete this file.
EOF

chmod 600 /root/.server-panel-credentials

# Installation complete
echo -e "${GREEN}Installation completed successfully!${NC}"
echo -e "${GREEN}Panel URL: ${PANEL_URL}${NC}"
echo -e "${GREEN}Admin Username: admin${NC}"
echo -e "${GREEN}Admin Password: ${PANEL_ADMIN_PASSWORD}${NC}"
echo -e "${BLUE}All credentials have been saved to: /root/.server-panel-credentials${NC}"
echo -e "${RED}IMPORTANT: Please save these credentials and delete /root/.server-panel-credentials file${NC}"

# Test the installation without SSL
echo -e "${BLUE}Testing installation...${NC}"
curl -I "${PANEL_URL}" 2>/dev/null | head -n 1 | grep "HTTP/1.1 200" > /dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Panel is accessible and running correctly!${NC}"
else
    echo -e "${RED}Panel might not be accessible. Please check the Apache error logs.${NC}"
fi 
