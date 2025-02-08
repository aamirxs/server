-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Server monitoring stats
CREATE TABLE server_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cpu_usage FLOAT,
    memory_usage FLOAT,
    disk_usage FLOAT,
    network_in BIGINT,
    network_out BIGINT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Python projects
CREATE TABLE python_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    git_url VARCHAR(255),
    python_version VARCHAR(10),
    status ENUM('running', 'stopped', 'error') DEFAULT 'stopped',
    port INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Scheduled tasks (Cron jobs)
CREATE TABLE scheduled_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    command TEXT NOT NULL,
    schedule VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_run TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- SSL certificates
CREATE TABLE ssl_certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    domain VARCHAR(255) NOT NULL,
    certificate_path VARCHAR(255),
    private_key_path VARCHAR(255),
    expiry_date DATE,
    auto_renewal BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Backup configurations
CREATE TABLE backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    path VARCHAR(255) NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
    retention_days INT DEFAULT 30,
    last_backup TIMESTAMP NULL,
    status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Activity logs
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
); 