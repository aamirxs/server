# Server Management Panel

A powerful web-based server management panel for Ubuntu servers that provides file management, live terminal, Python deployment, system monitoring, and backup management capabilities.

## Features

- 📁 File Manager
  - Upload/Download files
  - Create/Edit/Delete files and directories
  - File permissions management
  - Archive/Extract functionality

- 🖥️ Live Terminal
  - Full terminal access through web interface
  - Command history
  - Multi-session support

- 🐍 Python Deployment
  - Deploy Python applications
  - Git integration
  - Virtual environment management
  - Requirements installation
  - Process management

- 📊 System Monitoring
  - CPU usage tracking
  - Memory usage monitoring
  - Disk space analysis
  - Network traffic monitoring
  - Process management

- 💾 Backup Management
  - Scheduled backups
  - Custom backup paths
  - Retention policy
  - One-click restore

## Quick Installation
- bash
- wget https://raw.githubusercontent.com/your-repo/server-panel/main/setup.sh
- chmod +x setup.sh
- sudo ./setup.sh

The installation script will:
1. Install all required dependencies
2. Configure Apache and MySQL
3. Set up the panel
4. Create admin credentials
5. Provide you with access URL and login details

## System Requirements

- Ubuntu 20.04 LTS or higher
- 1 GB RAM minimum (2 GB recommended)
- 20 GB disk space
- Root access

## Required Packages

The setup script will automatically install:
- Apache2
- PHP 7.4+ with required extensions
- MySQL Server
- Git
- Node.js and npm
- Python 3

## Security

- All passwords are automatically generated during installation
- Database credentials are stored securely
- File permissions are set according to best practices
- Regular security updates recommended

## Default Credentials

After installation, you can find your credentials in:
