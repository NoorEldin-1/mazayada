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
php artisan permission:cache-reset || true

# ────────────────────────────────────────────────
# 4. Cache configuration, routes, views, events
# ────────────────────────────────────────────────
echo "⚡ Caching configuration..."
php artisan config:cache

# NOTE: `route:cache` is intentionally skipped — routes/web.php has a closure
# route (lang.switch) that Laravel cannot serialize, so route:cache would fail.
# Routes run uncached (negligible cost).

echo "⚡ Caching views..."
php artisan view:cache

echo "⚡ Caching events..."
php artisan event:cache

# ────────────────────────────────────────────────
# 5. Storage symbolic link (idempotent)
# ────────────────────────────────────────────────
echo "🔗 Ensuring storage link..."
# A stale plain `public/storage` directory (not a symlink) makes storage:link a
# silent no-op and 404s every uploaded auction photo — remove it first, then link.
if [ -e public/storage ] && [ ! -L public/storage ]; then
  echo "   public/storage is not a symlink — recreating it."
  rm -rf public/storage
fi
php artisan storage:link 2>/dev/null || true

# Generated PDFs (award / condition book / receipt / delivery report) live on the
# PRIVATE 'documents' disk — never the public symlink. Auction photos live on the
# PUBLIC disk (served via /storage). mpdf needs a writable temp dir.
mkdir -p storage/app/private/documents storage/app/mpdf storage/app/public/auctions

# ────────────────────────────────────────────────
# 6. Fix file permissions
# ────────────────────────────────────────────────
echo "🔒 Fixing permissions..."
chmod -R 775 storage bootstrap/cache

# ────────────────────────────────────────────────
# 7. Restart queue workers so they reload the new code.
#    Harmless if no worker is running.
# ────────────────────────────────────────────────
echo "🔁 Restarting queue workers..."
php artisan queue:restart || true

echo "✅ Deployment complete!"
