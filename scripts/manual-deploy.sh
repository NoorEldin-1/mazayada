#!/bin/bash
# ============================================================
#  Mazayada — Manual Deploy Script
#  ------------------------------------------------------------
#  Run this on the production server (as the site user) to pull
#  the latest code and apply EVERYTHING needed for a correct
#  deploy: dependencies, DB migrations, roles/permissions,
#  caches, storage link, permissions, queue workers.
#
#  USAGE (from your SSH session):
#     cd /home/mazayada.findosystem.com/laravel
#     bash scripts/manual-deploy.sh
#
#  Every step is idempotent — safe to run on every deploy.
#  No `npm build` is needed: the site uses a static CSS file,
#  not Vite-built assets.
# ============================================================

set -euo pipefail

# Wrapped in a function so the whole script is parsed BEFORE the
# `git reset` step can ever modify this file mid-run.
deploy() {
  # Always operate from the project root (this file lives in scripts/).
  cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
  echo "📂 Project: $(pwd)"

  # ── 1/8  Pull latest code ─────────────────────────────────
  #   reset --hard syncs tracked files to origin/main. It never
  #   touches .env, vendor/, public/build/ or storage/ (gitignored).
  echo "⤵️  [1/8] Pulling latest code from GitHub (main)..."
  git fetch origin main
  git reset --hard origin/main

  # ── 2/8  PHP dependencies (production) ────────────────────
  #   Needed when composer.json/lock changed; also refreshes the
  #   optimized autoloader for any new classes. Harmless otherwise.
  echo "📦 [2/8] Installing Composer dependencies..."
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress

  # ── 3/8  Database migrations ──────────────────────────────
  #   Applies new migrations. Prints "Nothing to migrate" if none.
  echo "🗄️  [3/8] Running database migrations..."
  php artisan migrate --force

  # ── 4/8  Roles & permissions (RBAC) ───────────────────────
  #   RolesPermissionsSeeder is idempotent (firstOrCreate). Run it
  #   every deploy so new permissions are registered, then reset
  #   Spatie's permission cache.
  echo "🔐 [4/8] Syncing roles & permissions..."
  php artisan db:seed --class=RolesPermissionsSeeder --force
  php artisan permission:cache-reset || true

  # ── 5/8  Clear stale caches ───────────────────────────────
  #   Wipes old config/route/view/event/compiled caches so the new
  #   code, routes and translations take effect.
  echo "🧹 [5/8] Clearing stale caches..."
  php artisan optimize:clear

  # ── 6/8  Rebuild caches for performance ───────────────────
  #   NOTE: `route:cache` is intentionally SKIPPED — routes/web.php
  #   has a closure route (lang.switch) that Laravel cannot
  #   serialize, so route:cache would fail. Routes run uncached
  #   (negligible cost). See note at the bottom to enable it.
  echo "⚡ [6/8] Rebuilding caches (config, events, views)..."
  php artisan config:cache
  php artisan event:cache
  php artisan view:cache

  # ── 7/8  Storage link + writable permissions ──────────────
  echo "🔗 [7/8] Storage link + permissions..."
  php artisan storage:link 2>/dev/null || true
  chmod -R 775 storage bootstrap/cache

  # ── 8/8  Restart queue workers ────────────────────────────
  #   Signals running workers to reload the new code. Harmless if
  #   no worker is running.
  echo "🔁 [8/8] Restarting queue workers..."
  php artisan queue:restart || true

  echo ""
  echo "✅ Deploy complete — $(php artisan --version)"
}

deploy "$@"

# ============================================================
#  REFERENCE — run these MANUALLY only when relevant
#  (NOT part of a routine deploy):
#
#  • Reverb (WebSockets) — if you run it as a daemon and changed
#    broadcasting/event code, restart it:
#       pkill -f 'reverb:start' || true
#       nohup php artisan reverb:start > storage/logs/reverb.log 2>&1 &
#
#  • FIRST-TIME database setup ONLY (fresh DB) — these seed
#    reference/demo data and will DUPLICATE rows if re-run:
#       php artisan db:seed --class=WilayaSeeder --force
#       php artisan db:seed --class=CommuneSeeder --force
#       php artisan db:seed --class=CategorySeeder --force
#       php artisan db:seed --class=EntitySeeder --force
#       php artisan db:seed --class=AdminUserSeeder --force
#    ❗ NEVER run `php artisan db:seed` (no class) in production —
#       it also runs DemoDataSeeder (demo data).
#
#  • To enable route:cache later: convert the lang.switch closure
#    in routes/web.php to a controller method, then add
#    `php artisan route:cache` to step 6.
# ============================================================
