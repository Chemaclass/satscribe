#!/bin/bash
set -eu

echo "🎼 Running composer install..."
composer install --optimize-autoloader --no-interaction --prefer-dist

echo "📦 Installing npm dependencies..."
npm install

echo "🛠 Building frontend assets..."
npm run build

echo "🧹 Clearing and caching Laravel config..."
php artisan optimize

echo "🗄️ Running database migrations..."
php artisan migrate --force

SHARED_ENV=".env"
COMMIT=$(git rev-parse HEAD)
echo "🔄 $(date +'%T') - Saving LAST_COMMIT_HASH=$COMMIT"
if grep -q '^LAST_COMMIT_HASH=' "$SHARED_ENV"; then
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "s|^LAST_COMMIT_HASH=.*|LAST_COMMIT_HASH=$COMMIT|" "$SHARED_ENV"
    else
        sed -i "s|^LAST_COMMIT_HASH=.*|LAST_COMMIT_HASH=$COMMIT|" "$SHARED_ENV"
    fi
else
    echo "LAST_COMMIT_HASH=$COMMIT" >> "$SHARED_ENV"
fi
