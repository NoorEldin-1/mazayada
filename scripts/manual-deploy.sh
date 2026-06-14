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
#
#  NO `npm`/Vite build runs on the server. The dashboards' Vite
#  assets (public/build — dashboard.css/js + ApexCharts/Preline) are
#  pre-built locally and COMMITTED to git, so `git reset --hard`
#  (step 1) ships them. The public/auth pages still use the static
#  /css/mazayada.css. ❗ If you changed any dashboard CSS/JS, run
#  `npm run build` locally and commit public/build BEFORE deploying,
#  otherwise the dashboards will 500 (missing Vite manifest).
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
  #   touches .env, vendor/ or storage/ (gitignored). public/build IS
  #   tracked now, so the pre-built Vite assets are synced with it.
  echo "⤵️  [1/8] Pulling latest code from GitHub (main)..."
  git fetch origin main
  git reset --hard origin/main

  #   Fail fast if the committed Vite manifest is missing — the
  #   dashboard layouts use @vite() and would 500 without it.
  if [ ! -f public/build/manifest.json ]; then
    echo "❌ public/build/manifest.json is missing — the dashboard Vite assets"
    echo "   were not committed. Locally run 'npm run build', commit public/build,"
    echo "   push to main, then re-run this deploy. Aborting before any changes."
    exit 1
  fi

  # ── 2/8  PHP dependencies (production) ────────────────────
  #   Needed when composer.json/lock changed; also refreshes the
  #   optimized autoloader for any new classes. Harmless otherwise.
  echo "📦 [2/8] Installing Composer dependencies..."
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress

  # ── 3/8  Database migrations ──────────────────────────────
  #   Applies new migrations. Prints "Nothing to migrate" if none.
  echo "🗄️  [3/8] Running database migrations..."
  php artisan migrate --force

  # ── 4/8  Roles, permissions (RBAC) & reference data ───────
  #   RolesPermissionsSeeder is idempotent (firstOrCreate). Run it
  #   every deploy so new permissions are registered, then reset
  #   Spatie's permission cache.
  #
  #   CommuneSeeder loads the full 58-wilaya / 1541-commune list
  #   from database/data/communes.json. It is now IDEMPOTENT: it
  #   skips instantly when the table already holds the full list, and
  #   reloads (truncate + insert) only when it's missing/incomplete —
  #   e.g. servers still on the old 3-wilaya sample. Requires wilayas
  #   to already exist (WilayaSeeder — first-time setup below).
  #
  #   SystemSettingsSeeder seeds the runtime platform parameters
  #   (Section 8.2) and is IDEMPOTENT: it firstOrCreate's each key, so
  #   an admin-edited value in the system_settings table is never
  #   overwritten on re-deploy — only missing keys are (re)added.
  echo "🔐 [4/8] Syncing roles, permissions & reference data..."
  php artisan db:seed --class=RolesPermissionsSeeder --force
  php artisan db:seed --class=SystemSettingsSeeder --force
  php artisan db:seed --class=CommuneSeeder --force
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
  #   Generated PDFs (award / condition book / receipt / delivery report) are
  #   stored on the PRIVATE 'documents' disk (storage/app/private/documents) and
  #   served only via the gated /documents/{id}/download route — never the public
  #   symlink. The public /verify route (QR authenticity check) needs no auth.
  #   storage:link exposes auction photos (public disk) at /storage. Generated
  #   PDFs stay PRIVATE. mpdf needs a writable temp dir for Arabic-shaped PDFs.
  echo "🔗 [7/8] Storage link + permissions..."
  # A stale plain `public/storage` directory (not a symlink) makes storage:link
  # a silent no-op and 404s every uploaded auction photo — remove it first,
  # then (re)create the symlink.
  if [ -e public/storage ] && [ ! -L public/storage ]; then
    echo "   public/storage is not a symlink — recreating it."
    rm -rf public/storage
  fi
  php artisan storage:link 2>/dev/null || true
  mkdir -p storage/app/private/documents storage/app/mpdf storage/app/public/auctions
  chmod -R 775 storage bootstrap/cache

  # ── 8/8  Restart queue workers ────────────────────────────
  #   Signals running workers to reload the new code. Harmless if
  #   no worker is running.
  echo "🔁 [8/8] Restarting queue workers..."
  php artisan queue:restart || true

  # ── Reverb (WebSockets) — spec §6 live auctions ───────────
  #   Reverb relays broadcasts to browsers; the live-auction page and the
  #   BidPlaced / AuctionExtended / AuctionClosed events depend on it running.
  #   Reverb does NOT execute app event code (the PHP-FPM request posts the
  #   broadcast to it), so changing event classes needs no Reverb restart — but
  #   we (re)start it here so every deploy ends with a running daemon. Preferred
  #   setup is a supervised systemd unit (see the reference block at the bottom).
  echo "📡 [Reverb] Ensuring the WebSocket server is running..."
  if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files 2>/dev/null | grep -q '^mazayada-reverb\.service'; then
    if sudo -n systemctl restart mazayada-reverb 2>/dev/null; then
      echo "   restarted mazayada-reverb.service"
    else
      echo "   ⚠️  could not restart mazayada-reverb via passwordless sudo — restart it manually."
    fi
  else
    echo "   ℹ️  mazayada-reverb.service not installed — see the reference at the bottom"
    echo "      of this script to set up the systemd unit (one-time, recommended)."
  fi

  echo ""
  echo "✅ Deploy complete — $(php artisan --version)"
}

deploy "$@"

# ============================================================
#  REFERENCE — run these MANUALLY only when relevant
#  (NOT part of a routine deploy):
#
#  • Reverb (WebSockets) — spec §6 live auctions. Step [Reverb] above
#    restarts it automatically IF the systemd unit below exists and the
#    site user has passwordless sudo for it. ONE-TIME setup (as root):
#
#       cat >/etc/systemd/system/mazayada-reverb.service <<'UNIT'
#       [Unit]
#       Description=Mazayada Reverb WebSocket Server
#       After=network.target
#       [Service]
#       User=mazayada.findosystem.com
#       WorkingDirectory=/home/mazayada.findosystem.com/laravel
#       ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8080
#       Restart=always
#       RestartSec=3
#       [Install]
#       WantedBy=multi-user.target
#       UNIT
#       systemctl daemon-reload && systemctl enable --now mazayada-reverb
#
#    Allow the deploy step to restart it without a password (visudo):
#       mazayada.findosystem.com ALL=(root) NOPASSWD: /usr/bin/systemctl restart mazayada-reverb
#
#    Reverb listens on 127.0.0.1:8080 (internal only). The browser reaches it
#    over wss://mazayada.findosystem.com/app/... via the OpenLiteSpeed Web
#    Socket Proxy (CyberPanel → Websites → vHost Conf): proxy URI /app to
#    127.0.0.1:8080. Set the production .env to REVERB_HOST=mazayada.findosystem.com,
#    REVERB_SCHEME=https, REVERB_PORT=443 (the committed JS reads these from the
#    server, not from build-time env). No new firewall port — all over 443.
#
#    Fallback without systemd (foreground daemon as the site user):
#       pkill -f 'reverb:start' || true
#       nohup php artisan reverb:start --host=127.0.0.1 --port=8080 > storage/logs/reverb.log 2>&1 &
#
#  • SCHEDULER (cron) — the daily KYC auto-suspension
#    (`kyc:suspend-stale`), the per-minute auction commands
#    (`auctions:activate`, `auctions:close`), the hourly deposit
#    settlement (`auctions:settle-deposits` — refunds losers / forfeits
#    defaulting winners after the final-payment deadline, spec §4 step 8),
#    and the daily final-payment reminder (`auctions:remind-final-payment`)
#    only run if a system cron invokes Laravel's scheduler every minute.
#    Verify this line exists in the site user's crontab (`crontab -l`):
#       * * * * * cd /home/mazayada.findosystem.com/laravel && php artisan schedule:run >> /dev/null 2>&1
#
#  • KYC / OTP EMAILS — approve/reject/suspend notifications and
#    registration OTP are sent by email (queued). They need (a) valid
#    MAIL_* settings in .env and (b) a running queue worker if
#    QUEUE_CONNECTION is not `sync`. Step 8 restarts the worker; make
#    sure one is actually supervised (e.g. systemd/supervisor running
#    `php artisan queue:work`). Mail failures are caught + logged, so
#    a misconfigured mailer never breaks a KYC decision.
#
#  • KYC DOCUMENTS are stored on the PRIVATE disk
#    (storage/app/private/kyc/...) and served only through gated
#    routes — never the public /storage symlink. The dir is created
#    on first upload; step 7's `chmod -R 775 storage` keeps it
#    writable. Ensure storage/ is owned by the php-fpm/web user.
#
#  • FIRST-TIME database setup ONLY (fresh DB) — these seed
#    reference/demo data and will DUPLICATE rows if re-run:
#       php artisan db:seed --class=WilayaSeeder --force
#       php artisan db:seed --class=CategorySeeder --force
#       php artisan db:seed --class=EntitySeeder --force
#       php artisan db:seed --class=AdminUserSeeder --force
#    (CommuneSeeder and SystemSettingsSeeder are idempotent and run in
#     step 4 above — do NOT list them here.) WilayaSeeder must run
#     before the first CommuneSeeder so the wilaya FK targets exist.
#    ❗ NEVER run `php artisan db:seed` (no class) in production —
#       it also runs DemoDataSeeder (demo data).
#
#  • To enable route:cache later: convert the lang.switch closure
#    in routes/web.php to a controller method, then add
#    `php artisan route:cache` to step 6.
# ============================================================
