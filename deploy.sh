#!/bin/bash
set -eu

PROJECT_DIR="$HOME/Code/satscribe"
BRANCH="main"

echo "🔄 Deploying latest Satscribe to $PROJECT_DIR"

cd "$PROJECT_DIR"

echo "📥 Pulling latest changes from Git..."
git checkout $BRANCH
git pull origin $BRANCH

echo "🎼 Running composer install..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "📦 Installing npm dependencies..."
npm install

echo "🛠 Building frontend assets..."
npm run build

echo "🧹 Clearing and caching Laravel config..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

echo "✅ Deployment finished!"
