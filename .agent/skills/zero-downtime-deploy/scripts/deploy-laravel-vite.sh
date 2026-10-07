#!/usr/bin/env bash
# ==============================================================================
# ZERO-DOWNTIME ATOMIC DEPLOY SCRIPT (LARAVEL + VITE)
# Dùng chung cho các dự án: nhm-ads-agency-adsviet, massagehome, hm-ride, v.v.
# ==============================================================================
set -e

BRANCH="${1:-master}"

echo "🚀 [1/6] Pulling code from branch '$BRANCH'..."
git pull origin "$BRANCH"

echo "📦 [2/6] Checking PHP dependencies..."
if [ -f "composer.lock" ]; then
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
fi

echo "🗄️ [3/6] Running database migrations..."
php artisan migrate --force

echo "🎨 [4/6] Building frontend into temporary directory (Atomic Build)..."
if [ -f "package-lock.json" ]; then
    npm ci --prefer-offline --no-audit || npm install --prefer-offline --no-audit
else
    npm install --prefer-offline --no-audit
fi

# Build into isolated temp directory to prevent deleting active manifest
npx vite build --outDir public/build_tmp

# Integrity check: abort if manifest missing
if [ ! -f "public/build_tmp/manifest.json" ]; then
    echo "❌ [ERROR] Vite manifest.json was not generated in public/build_tmp!"
    echo "Aborting atomic swap to keep the existing application online."
    rm -rf public/build_tmp
    exit 1
fi

echo "🔄 [5/6] Swapping build directories (0.01s atomic swap)..."
rm -rf public/build_old
mv public/build public/build_old 2>/dev/null || true
mv public/build_tmp public/build
rm -rf public/build_old

echo "⚡ [6/6] Clearing and caching Laravel optimizations..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

echo "🎉 [DEPLOY SUCCESSFUL] Zero-Downtime Deployment completed!"
