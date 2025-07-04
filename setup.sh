#!/bin/bash
set -eu

CURRENT_USER=$(whoami)

echo "🔐 Setting initial Laravel permissions and ownership..."

# Give the current user and web server access to the full project
sudo chown -R "$CURRENT_USER":www-data .

# Ensure storage and bootstrap/cache are writable
sudo chmod -R 775 storage bootstrap/cache

# Make sure config.php exists and is writable by the web server
sudo touch bootstrap/cache/config.php
sudo chown www-data:www-data bootstrap/cache/config.php
sudo chmod 664 bootstrap/cache/config.php

echo "✅ Initial setup completed successfully. You can now run ./install.sh"
