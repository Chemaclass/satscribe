#!/bin/bash
set -euo pipefail

#######################
# CONFIGURATION
#######################

PROJECT_NAME="satscribe"
DEPLOY_DIR="$HOME/$PROJECT_NAME"
LOCAL_REPO_DIR="$HOME/Code/$PROJECT_NAME"
BRANCH="main"
REMOTE_REPO="git@github.com:Chemaclass/satscribe.git"
RELEASES_DIR="$DEPLOY_DIR/releases"
CURRENT_DIR="$DEPLOY_DIR/current"
KEEP_RELEASES=5
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
NEW_RELEASE_DIR="$RELEASES_DIR/$TIMESTAMP"

#######################
# FUNCTIONS
#######################

log() {
  echo "[$(date +"%H:%M:%S")] $*"
}

cleanup_old_releases() {
  log "🧹 Cleaning up old releases (keeping last $KEEP_RELEASES)..."
  cd "$RELEASES_DIR"
  local releases
  releases=($(ls -1t))

  if (( ${#releases[@]} > KEEP_RELEASES )); then
    local remove_releases=("${releases[@]:KEEP_RELEASES}")
    for old_release in "${remove_releases[@]}"; do
      log "🗑️ Removing old release: $old_release"
      sudo rm -rf "$RELEASES_DIR/$old_release"
    done
  else
    log "✅ No old releases to remove."
  fi
}

check_commands() {
  for cmd in git composer npm php; do
    if ! command -v "$cmd" &> /dev/null; then
      echo "❌ Error: Required command '$cmd' is not available."
      exit 1
    fi
  done
}

rollback_on_failure() {
  log "❌ Deployment failed. Cleaning up..."
  sudo rm -rf "$NEW_RELEASE_DIR"
  log "🧹 Cleaned up incomplete release: $NEW_RELEASE_DIR"
  exit 1
}

#######################
# DEPLOY PROCESS
#######################

log "🚀 Starting deployment of $PROJECT_NAME"

# Pre-checks
check_commands

# Ensure releases dir exists
mkdir -p "$RELEASES_DIR"

# Setup trap to rollback if anything fails
trap rollback_on_failure ERR

# Clone latest code from GitHub
log "🔄 Cloning remote repository..."
git clone --branch "$BRANCH" --depth=1 "$REMOTE_REPO" "$NEW_RELEASE_DIR"

# Copy persistent .env
if [ -e "$CURRENT_DIR/.env" ]; then
  log "📄 Copying .env from current release..."
  cp "$CURRENT_DIR/.env" "$NEW_RELEASE_DIR/.env"
elif [ -e "$LOCAL_REPO_DIR/.env" ]; then
  log "📄 Current release has no .env, copying fallback from local repo..."
  cp "$LOCAL_REPO_DIR/.env" "$NEW_RELEASE_DIR/.env"
else
  log "❌ No .env found in current release or fallback repo. Aborting."
  rollback_on_failure
fi

# Always fix .env permissions
chmod 644 "$NEW_RELEASE_DIR/.env"

# Copy persistent database
if [ -e "$CURRENT_DIR/database/database.sqlite" ]; then
  log "🗄️ Copying database from current release..."
  mkdir -p "$NEW_RELEASE_DIR/database"
  cp "$CURRENT_DIR/database/database.sqlite" "$NEW_RELEASE_DIR/database/database.sqlite"
elif [ -e "$LOCAL_REPO_DIR/database/database.sqlite" ]; then
  log "🗄️ Current release has no database, copying fallback from local repo..."
  mkdir -p "$NEW_RELEASE_DIR/database"
  cp "$LOCAL_REPO_DIR/database/database.sqlite" "$NEW_RELEASE_DIR/database/database.sqlite"
else
  log "❌ No database.sqlite found in current release or fallback repo. Aborting."
  rollback_on_failure
fi

# 🔒 Fix database folder and file permissions
log "🔒 Fixing database folder and file permissions..."
sudo chown -R $USER:www-data "$NEW_RELEASE_DIR/database"
find "$NEW_RELEASE_DIR/database" -type d -exec chmod 775 {} \;
find "$NEW_RELEASE_DIR/database" -type f -exec chmod 664 {} \;

# Go into the new release
cd "$NEW_RELEASE_DIR"

# Ensure storage and cache directories exist
log "📂 Ensuring storage and cache directories exist..."
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# 🔒 Fix permissions after creating folders
log "🔒 Fixing permissions..."
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Install backend dependencies
log "🎼 Running composer install..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Install frontend dependencies
log "📦 Installing full npm dependencies (including dev)..."
npm install --prefer-offline

# Build frontend assets
log "🛠 Building frontend assets..."
npm run build

# (Optional) Remove dev dependencies after build
log "🧹 Pruning dev dependencies..."
npm prune --omit=dev

# Laravel cache clearing and caching
log "🧹 Clearing and caching Laravel configuration..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
log "🗄️ Running database migrations..."
php artisan migrate --force

# Disable rollback trap
trap - ERR

# Update symlink
log "🔗 Updating current symlink..."
ln -sfn "$NEW_RELEASE_DIR" "$CURRENT_DIR"

# 🔒 Fix database folder and file permissions in current release
log "🔒 Fixing database folder and file permissions in current release..."
sudo chown -R $USER:www-data "$CURRENT_DIR/database"
sudo chmod -R 775 "$CURRENT_DIR/database"

# 🔒 Fix storage and cache permissions
log "🔒 Fixing permissions for storage and cache in current release..."
sudo chown -R $USER:www-data "$CURRENT_DIR/storage" "$CURRENT_DIR/bootstrap/cache"
sudo chmod -R 775 "$CURRENT_DIR/storage" "$CURRENT_DIR/bootstrap/cache"

# Cleanup old releases
cleanup_old_releases

log "✅ Deployment finished successfully! Now serving from: $NEW_RELEASE_DIR"
