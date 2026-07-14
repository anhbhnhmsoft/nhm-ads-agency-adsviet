#!/bin/bash
# Deploy script - run after every git pull
# Usage: bash deploy.sh

set -e

echo "🚀 Starting deploy..."

# 1. Pull latest code
echo "📥 Pulling latest code..."
git pull

# 2. Fix storage permissions
echo "🔧 Fixing storage permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 3. Create log directories if missing
mkdir -p storage/logs/actions storage/logs/errors storage/logs/commands
chown -R www-data:www-data storage/logs
chmod -R 775 storage/logs

# 4. Install PHP dependencies (skip if running as root without flag)
echo "📦 Installing composer dependencies..."
if [ "$(id -u)" = "0" ]; then
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
else
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 5. Run migrations
echo "🗃️ Running migrations..."
php artisan migrate --force

# 6. Clear & rebuild caches
echo "🧹 Clearing caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Build frontend assets
echo "🏗️ Building frontend assets..."
if [ -f "yarn.lock" ]; then
    yarn install --frozen-lockfile && yarn build
elif [ -f "package-lock.json" ]; then
    npm ci && npm run build
fi

# 8. Fix permissions again (after build creates new files)
echo "🔧 Final permission fix..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 9. Restart queue worker if running
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || true

echo "✅ Deploy complete!"
