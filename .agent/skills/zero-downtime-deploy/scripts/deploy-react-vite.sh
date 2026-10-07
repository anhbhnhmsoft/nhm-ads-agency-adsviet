#!/usr/bin/env bash
# ==============================================================================
# ZERO-DOWNTIME ATOMIC DEPLOY SCRIPT (REACT / VUE / SPA STANDALONE)
# Dùng cho các dự án như: hm-web-admin, v.v.
# ==============================================================================
set -e

BRANCH="${1:-main}"
TARGET_DIR="${2:-/var/www/html/public_root/admin}"

echo "🚀 [1/4] Pulling code from branch '$BRANCH'..."
git pull origin "$BRANCH"

echo "📦 [2/4] Installing dependencies..."
if [ -f "package-lock.json" ]; then
    npm ci --prefer-offline --no-audit || npm install --prefer-offline --no-audit
else
    npm install --prefer-offline --no-audit
fi

echo "🎨 [3/4] Building React app..."
npm run build

echo "🔄 [4/4] Performing atomic swap into webroot '$TARGET_DIR'..."
mkdir -p "$TARGET_DIR"
rm -rf "${TARGET_DIR}_tmp" "${TARGET_DIR}_old"
cp -r dist "${TARGET_DIR}_tmp"

# Atomic rename swap
mv "$TARGET_DIR" "${TARGET_DIR}_old" 2>/dev/null || true
mv "${TARGET_DIR}_tmp" "$TARGET_DIR"
rm -rf "${TARGET_DIR}_old"

echo "🎉 [DEPLOY SUCCESSFUL] React Web Admin deployed without downtime!"
