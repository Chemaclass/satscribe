#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

echo "🛠 $(date +'%F %T') - Starting zero-downtime deployment..."

# CONFIG
REPO_URL="git@github.com:Chemaclass/satscribe.git"
BRANCH="${1:-${BRANCH:-main}}"
BASE_DIR="/var/www/html/satscribe"
RELEASES_DIR="$BASE_DIR/releases"
CURRENT_LINK="$BASE_DIR/current"
SHARED_ENV="$BASE_DIR/shared/.env"
SHARED_STORAGE="$BASE_DIR/shared/storage"
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
NEW_RELEASE_DIR="$RELEASES_DIR/$TIMESTAMP"

# Trap to clean up on error
trap 'echo "❌ $(date +%F %T) - Deployment failed. Cleaning up..."; rm -rf "$NEW_RELEASE_DIR"; exit 1' ERR

mkdir -p "$RELEASES_DIR"

echo "📥 $(date +'%T') - Cloning '$BRANCH' into $NEW_RELEASE_DIR"
git clone --branch "$BRANCH" --depth 1 "$REPO_URL" "$NEW_RELEASE_DIR"

echo "🔗 $(date +'%T') - Linking shared .env and storage"
ln -sfn "$SHARED_ENV" "$NEW_RELEASE_DIR/.env"
rm -rf "$NEW_RELEASE_DIR/storage"
ln -sfn "$SHARED_STORAGE" "$NEW_RELEASE_DIR/storage"

# Capture and store commit hash
cd "$NEW_RELEASE_DIR"
COMMIT=$(git rev-parse HEAD)
echo "🔄 $(date +'%T') - Saving LAST_COMMIT_HASH=$COMMIT"
if grep -q '^LAST_COMMIT_HASH=' "$SHARED_ENV"; then
    sed -i "s|^LAST_COMMIT_HASH=.*|LAST_COMMIT_HASH=$COMMIT|" "$SHARED_ENV"
else
    echo "LAST_COMMIT_HASH=$COMMIT" >> "$SHARED_ENV"
fi

echo "📦 $(date +'%T') - Running composer install"
composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --no-progress

echo "📦 $(date +'%T') - Installing npm dependencies"
npm ci --prefer-offline --no-audit

echo "🛠 $(date +'%T') - Building frontend assets"
npm run build

echo "🗄️  $(date +'%T') - Running database migrations"
php artisan migrate --force

echo "🔁 $(date +'%T') - Switching current symlink to $NEW_RELEASE_DIR"
ln -sfn "$NEW_RELEASE_DIR" "$CURRENT_LINK"

echo "🧹 $(date +'%T') - Clearing and caching Laravel config"
cd "$CURRENT_LINK"
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🧹 $(date +'%T') - Cleaning up old releases (keeping 10)"
cd "$RELEASES_DIR"
ls -1dt */ | tail -n +11 | xargs -r rm -rf --

# Reload PHP-FPM to ensure the new config is picked up
PHP_FPM_SERVICE=$(systemctl list-units --type=service | grep php | grep fpm | awk '{print $1}' | head -n1)
if [[ -n "$PHP_FPM_SERVICE" ]]; then
  echo "🔁 $(date +'%T') - Reloading $PHP_FPM_SERVICE to apply changes"
  sudo systemctl reload "$PHP_FPM_SERVICE"
else
  echo "⚠️ $(date +'%T') - Could not detect PHP-FPM service name. Please reload manually."
fi

echo "✅ $(date +'%F %T') - Deployment complete: now serving $CURRENT_LINK"
