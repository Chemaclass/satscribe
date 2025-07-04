#!/bin/bash
set -euo pipefail

echo "🛠 Starting deploy.sh..."

# Verbose logging to troubleshoot issues
exec 1> >(tee -a /tmp/deploy.log) 2>&1

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

echo "🧼 Cleaning working directory (before reset)"
git status

echo "🧹 git reset --hard HEAD"
git reset --hard HEAD || { echo "❌ git reset failed"; exit 1; }

echo "🧽 git clean -xfd"
git clean -xfd || { echo "❌ git clean failed"; exit 1; }

echo "🔄 Fetching latest..."
git fetch origin || { echo "❌ fetch failed"; exit 1; }

echo "📌 Checking out $BRANCH"
git checkout "$BRANCH" || { echo "❌ checkout failed"; exit 1; }

echo "🚿 Resetting to origin/$BRANCH"
git reset --hard "origin/$BRANCH" || { echo "❌ reset to remote failed"; exit 1; }

echo "✅ git status after cleanup:"
git status

# Update LAST_RELEASE_COMMIT in .env
if [ -f .env ]; then
  LATEST_COMMIT=$(git rev-parse HEAD)
  if grep -q '^LAST_RELEASE_COMMIT=' .env; then
    sed -i "s/^LAST_RELEASE_COMMIT=.*/LAST_RELEASE_COMMIT=$LATEST_COMMIT/" .env
  else
    echo "LAST_RELEASE_COMMIT=$LATEST_COMMIT" >> .env
  fi
fi

if [ -f ./install.sh ]; then
  ./install.sh
else
  echo "⚠️ No install.sh found, skipping"
fi

echo "✅ Deployment finished!"
