#!/bin/bash
set -eu

echo "🎼 Running composer install..."
composer install --optimize-autoloader --no-interaction --prefer-dist

echo "📦 Installing npm dependencies..."
npm install

echo "🛠 Building frontend assets..."
npm run build

echo "🧹 Clearing and caching Laravel config..."
php artisan route:clear
php artisan view:clear

php artisan route:cache
php artisan view:cache

echo "🗄️ Running database migrations..."
php artisan migrate --force
