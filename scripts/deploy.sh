#!/bin/bash
# ============================================================
#  Mazayada — Post-Deploy Script
#  Runs on the production server after rsync delivers code.
# ============================================================

set -euo pipefail

echo "🚀 Starting post-deploy..."

# ────────────────────────────────────────────────
# 1. Install PHP dependencies (production only)
# ────────────────────────────────────────────────
echo "📦 Installing Composer dependencies..."
composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --no-progress

# ────────────────────────────────────────────────
# 2. Run database migrations
# ────────────────────────────────────────────────
echo "🗄️ Running migrations..."
php artisan migrate --force

# ────────────────────────────────────────────────
# 3. Seed RBAC roles & permissions
#    Uses --force to run in production.
#    RolesPermissionsSeeder uses firstOrCreate,
#    so it is safe to run on every deployment.
# ────────────────────────────────────────────────
echo "🔐 Seeding roles & permissions..."
php artisan db:seed --class=RolesPermissionsSeeder --force

# ────────────────────────────────────────────────
# 4. Cache configuration, routes, views, events
# ────────────────────────────────────────────────
echo "⚡ Caching configuration..."
php artisan config:cache

echo "⚡ Caching routes..."
php artisan route:cache

echo "⚡ Caching views..."
php artisan view:cache

echo "⚡ Caching events..."
php artisan event:cache

# ────────────────────────────────────────────────
# 5. Storage symbolic link (idempotent)
# ────────────────────────────────────────────────
echo "🔗 Ensuring storage link..."
php artisan storage:link 2>/dev/null || true

# ────────────────────────────────────────────────
# 6. Fix file permissions
# ────────────────────────────────────────────────
echo "🔒 Fixing permissions..."
chmod -R 775 storage bootstrap/cache

echo "✅ Deployment complete!"
