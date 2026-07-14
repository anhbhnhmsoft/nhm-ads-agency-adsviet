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
chown -R www-data:www-data storage
chmod -R 775 storage

# 3. Install PHP dependencies
echo "📦 Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Run migrations
echo "🗃️ Running migrations..."
php artisan migrate --force

# 5. Clear & rebuild caches
echo "🧹 Clearing caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Build frontend assets
echo "🏗️ Building frontend assets..."
yarn install --frozen-lockfile
yarn build

# 7. Fix permissions again (after build creates new files)
echo "🔧 Final permission fix..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 8. Restart queue worker if running
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || true

echo "✅ Deploy complete!"
