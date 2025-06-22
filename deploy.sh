#!/bin/bash
set -eu

PROJECT_DIR="$HOME/Code/satscribe"
BRANCH="main"

echo "🔄 Deploying latest Satscribe to $PROJECT_DIR"

cd "$PROJECT_DIR"

echo "📥 Pulling latest changes from Git..."
git checkout $BRANCH
git pull origin $BRANCH

# Update LAST_RELEASE_COMMIT in .env with the latest commit hash
if [ -f .env ]; then
    LATEST_COMMIT=$(git rev-parse HEAD)
    if grep -q '^LAST_RELEASE_COMMIT=' .env; then
        sed -i "s/^LAST_RELEASE_COMMIT=.*/LAST_RELEASE_COMMIT=$LATEST_COMMIT/" .env
    else
        echo "LAST_RELEASE_COMMIT=$LATEST_COMMIT" >> .env
    fi
fi

./install.sh

echo "✅ Deployment finished!"
