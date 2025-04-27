#!/bin/bash
set -eu

PROJECT_DIR="$HOME/Code/satscribe"
BRANCH="main"

echo "🔄 Deploying latest Satscribe to $PROJECT_DIR"

cd "$PROJECT_DIR"

echo "📥 Pulling latest changes from Git..."
git checkout $BRANCH
git pull origin $BRANCH

./install.sh

echo "✅ Deployment finished!"
