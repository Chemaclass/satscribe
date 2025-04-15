#!/bin/bash
set -eu

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
php artisan migrate
