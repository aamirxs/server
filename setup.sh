#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration variables
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 12)
PANEL_DB_PASSWORD=$(openssl rand -base64 12)
PANEL_ADMIN_PASSWORD=$(openssl rand -base64 12)
PANEL_URL="http://$(curl -s ifconfig.me)"
INSTALL_DIR="/var/www/server-panel"

echo -e "${GREEN}Starting Enhanced Server Panel Installation...${NC}"

# Function to check system requirements
check_requirements() {
    echo -e "${BLUE}Checking system requirements...${NC}"
    
    # Check CPU cores
    CPU_CORES=$(nproc)
    if [ "$CPU_CORES" -lt 2 ]; then
        echo -e "${YELLOW}Warning: Recommended minimum 2 CPU cores. Found: $CPU_CORES${NC}"
    fi
    
    # Check RAM
    TOTAL_RAM=$(free -m | awk '/^Mem:/{print $2}')
    if [ "$TOTAL_RAM" -lt 1024 ]; then
        echo -e "${YELLOW}Warning: Recommended minimum 1GB RAM. Found: ${TOTAL_RAM}MB${NC}"
    fi
    
    # Check disk space
    FREE_DISK=$(df -m / | awk 'NR==2 {print $4}')
    if [ "$FREE_DISK" -lt 5120 ]; then
        echo -e "${RED}Error: Minimum 5GB free disk space required. Found: ${FREE_DISK}MB${NC}"
        exit 1
    fi
}

# Install dependencies
install_dependencies() {
    echo -e "${BLUE}Installing dependencies...${NC}"
    
    # Update package list
    apt-get update
    
    # Install required packages
    apt-get install -y \
        apache2 \
        php \
        php-mysql \
        php-gd \
        php-curl \
        php-zip \
        php-mbstring \
        php-xml \
        mysql-server \
        python3 \
        python3-pip \
        python3-venv \
        nodejs \
        npm \
        git \
        zip \
        unzip \
        supervisor \
        redis-server

    # Install Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    
    # Install Node.js dependencies
    npm install -g pm2
}

# Configure Apache
configure_apache() {
    echo -e "${BLUE}Configuring Apache...${NC}"
    
    # Create Apache configuration
    cat > /etc/apache2/sites-available/server-panel.conf <<EOF
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot $INSTALL_DIR
    
    <Directory $INSTALL_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/panel-error.log
    CustomLog \${APACHE_LOG_DIR}/panel-access.log combined
    
    <IfModule mod_headers.c>
        Header set X-Content-Type-Options "nosniff"
        Header set X-Frame-Options "SAMEORIGIN"
        Header set X-XSS-Protection "1; mode=block"
        Header set Referrer-Policy "strict-origin-when-cross-origin"
    </IfModule>
</VirtualHost>
EOF

    # Enable required Apache modules
    a2enmod rewrite
    a2enmod headers
    
    # Enable site and restart Apache
    a2ensite server-panel
    a2dissite 000-default
    systemctl restart apache2
}

# Configure MySQL
configure_mysql() {
    echo -e "${BLUE}Configuring MySQL...${NC}"
    
    # Secure MySQL installation
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$MYSQL_ROOT_PASSWORD';"
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Create panel database and user
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE server_panel;"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY '$PANEL_DB_PASSWORD';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON server_panel.* TO 'panel_user'@'localhost';"
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;"
}

# Install panel files
install_panel() {
    echo -e "${BLUE}Installing panel files...${NC}"
    
    # Create installation directory
    mkdir -p $INSTALL_DIR
    cd $INSTALL_DIR

    # Clone repository or copy files
    git clone https://github.com/aamirxs/server-panel.git .
    
    # Create necessary directories
    mkdir -p {assets,config,includes,modules,storage}/{css,js,img}
    mkdir -p modules/{dashboard,file-manager,terminal,python-deploy,monitoring,backup}
    mkdir -p storage/{logs,backups,temp}
    
    # Set permissions
    chown -R www-data:www-data $INSTALL_DIR
    find $INSTALL_DIR -type f -exec chmod 644 {} \;
    find $INSTALL_DIR -type d -exec chmod 755 {} \;
    chmod -R 775 $INSTALL_DIR/storage
}

# Configure panel
configure_panel() {
    echo -e "${BLUE}Configuring panel...${NC}"
    
    # Create configuration file
    cat > $INSTALL_DIR/config/database.php <<EOF
<?php
return [
    'host' => 'localhost',
    'database' => 'server_panel',
    'username' => 'panel_user',
    'password' => '$PANEL_DB_PASSWORD',
];
EOF

    # Create .env file
    cat > $INSTALL_DIR/.env <<EOF
APP_URL=$PANEL_URL
APP_ENV=production
DB_HOST=localhost
DB_DATABASE=server_panel
DB_USERNAME=panel_user
DB_PASSWORD=$PANEL_DB_PASSWORD
EOF

    # Set up admin user
    php $INSTALL_DIR/tools/create-admin.php admin $PANEL_ADMIN_PASSWORD
}

# Configure security
configure_security() {
    echo -e "${BLUE}Configuring security...${NC}"
    
    # Install and configure UFW firewall
    apt-get install -y ufw
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow http
    ufw allow https
    echo "y" | ufw enable
    
    # Install and configure Fail2ban
    apt-get install -y fail2ban
    cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
    systemctl restart fail2ban
}

# Main installation process
main() {
    check_requirements
    install_dependencies
    configure_apache
    configure_mysql
    install_panel
    configure_panel
    configure_security
    
    # Save credentials
    echo -e "Server Panel Credentials\n======================" > /root/.server-panel-credentials
    echo -e "URL: $PANEL_URL" >> /root/.server-panel-credentials
    echo -e "Admin Username: admin" >> /root/.server-panel-credentials
    echo -e "Admin Password: $PANEL_ADMIN_PASSWORD\n" >> /root/.server-panel-credentials
    echo -e "Database Credentials\n==================" >> /root/.server-panel-credentials
    echo -e "Root Password: $MYSQL_ROOT_PASSWORD" >> /root/.server-panel-credentials
    echo -e "Panel DB User: panel_user" >> /root/.server-panel-credentials
    echo -e "Panel DB Password: $PANEL_DB_PASSWORD" >> /root/.server-panel-credentials
    chmod 600 /root/.server-panel-credentials
    
    echo -e "${GREEN}Installation completed successfully!${NC}"
    echo -e "${GREEN}Panel URL: $PANEL_URL${NC}"
    echo -e "${GREEN}Admin Username: admin${NC}"
    echo -e "${GREEN}Admin Password: $PANEL_ADMIN_PASSWORD${NC}"
    echo -e "${BLUE}All credentials have been saved to: /root/.server-panel-credentials${NC}"
    echo -e "${RED}IMPORTANT: Please save these credentials and delete the credentials file${NC}"
}

# Run installation
main 