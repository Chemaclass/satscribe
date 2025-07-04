#!/bin/bash
set -eu

# Allow PROJECT_DIR override via env, fallback to auto-detect
PROJECT_DIR="${PROJECT_DIR:-}"

if [[ -z "$PROJECT_DIR" ]]; then
  if [[ -d "$HOME/Code/satscribe" ]]; then
    PROJECT_DIR="$HOME/Code/satscribe"
  elif [[ -d "/var/www/html/satscribe" ]]; then
    PROJECT_DIR="/var/www/html/satscribe"
  else
    echo "❌ Could not determine PROJECT_DIR. Set it manually via environment variable."
    exit 1
  fi
fi

# Allow BRANCH override via CLI or env
BRANCH="${1:-${BRANCH:-main}}"

echo "🔄 Deploying latest Satscribe to $PROJECT_DIR (branch: $BRANCH)"

cd "$PROJECT_DIR"

echo "📥 Pulling latest changes from Git..."
# Clean working directory to avoid pull issues
git reset --hard HEAD
git clean -fd
git fetch origin
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

# Update LAST_RELEASE_COMMIT in .env
if [ -f .env ]; then
  LATEST_COMMIT=$(git rev-parse HEAD)
  if grep -q '^LAST_RELEASE_COMMIT=' .env; then
    sed -i "s/^LAST_RELEASE_COMMIT=.*/LAST_RELEASE_COMMIT=$LATEST_COMMIT/" .env
  else
    echo "LAST_RELEASE_COMMIT=$LATEST_COMMIT" >> .env
  fi
fi

# Install/update dependencies or run build
if [ -f ./install.sh ]; then
  ./install.sh
else
  echo "⚠️ No install.sh script found. Skipping installation step."
fi

echo "✅ Deployment finished!"
