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

cd "$NEW_RELEASE_DIR"

# Run install if needed
if [ -f ./install.sh ]; then
  echo "🔧 Running install.sh"
  ./install.sh
else
  echo "⚠️ No install.sh found. Skipping setup."
fi

# Set last release commit
if [ -f .env ]; then
  COMMIT=$(git rev-parse HEAD)
  sed -i "/^LAST_RELEASE_COMMIT=/d" .env
  echo "LAST_RELEASE_COMMIT=$COMMIT" >> .env
fi

echo "🔁 Switching current symlink"
ln -sfn "$NEW_RELEASE_DIR" "$CURRENT_LINK"

echo "✅ Deployment complete: now serving $CURRENT_LINK"
