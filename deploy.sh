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

# 6. Build frontend assets (Zero-downtime: Atomic Build & Swap)
echo "🏗️ Building frontend assets (Atomic Build)..."
if [ -f "package-lock.json" ]; then
    # Cài package nếu có thay đổi (dùng npm install hoặc npm ci nếu có lockfile chuẩn)
    npm install --no-audit --no-fund
    
    # Build ra thư mục tạm để không làm gián đoạn public/build hiện tại
    rm -rf public/build_tmp
    npx vite build --outDir public/build_tmp
    
    # Hoán đổi thư mục nguyên tử (Atomic Swap trong 0.01 giây)
    rm -rf public/build_old
    mv public/build public/build_old 2>/dev/null || true
    mv public/build_tmp public/build
    rm -rf public/build_old
    echo "✨ Frontend build & swapped successfully!"
fi

# 7. Clear & rebuild caches (chỉ cache sau khi build frontend đã hoàn tất)
echo "🧹 Rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Fix permissions again (after build creates new files)
echo "🔧 Final permission fix..."
chown -R www-data:www-data storage bootstrap/cache public/build
chmod -R 775 storage bootstrap/cache public/build

# 9. Restart queue worker if running
echo "🔄 Restarting queue workers..."
php artisan queue:restart 2>/dev/null || true

echo "✅ Deploy complete!"
