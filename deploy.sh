#!/bin/bash
set -euo pipefail

#######################
# CONFIGURATION
#######################

PROJECT_NAME="satscribe"
DEPLOY_DIR="$HOME/$PROJECT_NAME"
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
      rm -rf "$RELEASES_DIR/$old_release"
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
  rm -rf "$NEW_RELEASE_DIR"
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

# Clone latest code
log "🔄 Cloning repository..."
git clone --branch "$BRANCH" --depth=1 "$REMOTE_REPO" "$NEW_RELEASE_DIR"

# Go into the new release
cd "$NEW_RELEASE_DIR"

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

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
log "🗄️ Running database migrations..."
php artisan migrate --force

# If everything up to here succeeded, disable the failure trap
trap - ERR

# Atomically update symlink
log "🔗 Updating current symlink..."
ln -sfn "$NEW_RELEASE_DIR" "$CURRENT_DIR"

# Cleanup old releases
cleanup_old_releases

log "✅ Deployment finished successfully! Now serving from: $NEW_RELEASE_DIR"
