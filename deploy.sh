#!/bin/bash
set -euo pipefail

echo "🛠 Starting zero-downtime deployment..."

# CONFIG
REPO_URL="git@github.com:Chemaclass/satscribe.git"
BRANCH="${1:-${BRANCH:-main}}"
BASE_DIR="/var/www/html/satscribe"
RELEASES_DIR="$BASE_DIR/releases"
CURRENT_LINK="$BASE_DIR/current"
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
NEW_RELEASE_DIR="$RELEASES_DIR/$TIMESTAMP"

# Ensure base dirs exist
mkdir -p "$RELEASES_DIR"

echo "📥 Cloning branch '$BRANCH' to $NEW_RELEASE_DIR"
git clone --branch "$BRANCH" --depth 1 "$REPO_URL" "$NEW_RELEASE_DIR"

# Link shared resources before anything Laravel-related
echo "🔗 Linking shared .env and storage"
ln -sfn "$BASE_DIR/shared/.env" "$NEW_RELEASE_DIR/.env"
ln -sfn "$BASE_DIR/shared/storage" "$NEW_RELEASE_DIR/storage"

# Persist the deployed commit hash outside of the cached config
COMMIT=$(cd "$NEW_RELEASE_DIR" && git rev-parse HEAD)
echo "🔄 Writing last_commit with $COMMIT"
echo "$COMMIT" > "$NEW_RELEASE_DIR/storage/last_commit.txt"

# Run install script if it exists
cd "$NEW_RELEASE_DIR"
if [ -f ./install.sh ]; then
  ./install.sh
else
  echo "⚠️ No install.sh found. Skipping setup."
fi

# Atomically switch the 'current' symlink to new release
echo "🔁 Switching current symlink to $NEW_RELEASE_DIR"
ln -sfn "$NEW_RELEASE_DIR" "$CURRENT_LINK"

# Clean up older releases, keeping only the 10 most recent
echo "🧹 Cleaning old releases (keeping latest 10)"
cd "$RELEASES_DIR"
ls -1dt */ | tail -n +11 | xargs -r rm -rf --

# Reload PHP-FPM to apply new config
PHP_FPM_SERVICE=$(systemctl list-units --type=service | grep php | grep fpm | awk '{print $1}' | head -n1)
if [[ -n "$PHP_FPM_SERVICE" ]]; then
  echo "🔁 Reloading $PHP_FPM_SERVICE to apply changes"
  sudo /bin/systemctl reload "$PHP_FPM_SERVICE"
else
  echo "⚠️ Could not detect PHP-FPM service name. Please reload manually."
fi

echo "✅ Deployment complete: now serving $CURRENT_LINK"
