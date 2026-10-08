#!/usr/bin/env bash
# Production deploy. Run on the server as the app user, from the repository root:
#   ./deploy.sh
# Pulls the current branch, installs dependencies, migrates and rebuilds caches.
set -euo pipefail

cd "$(dirname "$0")"
ROOT="$(pwd)"

echo "==> Preflight"
php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);' || { echo "PHP 8.4+ required"; exit 1; }
for ext in intl gd exif pdo_mysql mbstring zip fileinfo; do
  php -r "exit(extension_loaded('$ext') ? 0 : 1);" || { echo "Missing PHP extension: $ext"; exit 1; }
done
php -r 'exit(function_exists("imagewebp") ? 0 : 1);' || echo "WARNING: GD has no WebP support; image renditions will fail."
test -f backend/.env || { echo "backend/.env is missing (copy backend/.env.example and fill it in)"; exit 1; }
node -e 'const [a,b]=process.versions.node.split(".").map(Number); process.exit(a>20||(a===20&&b>=19)?0:1)' \
  || { echo "Node 20.19+ required for the PWA build (Vite 8)"; exit 1; }
grep -q '^APP_DEBUG=false' backend/.env || echo "WARNING: APP_DEBUG is not false in backend/.env"
grep -q '^VAPID_PRIVATE_KEY=.\+' backend/.env || echo "WARNING: no VAPID keys; push notifications are disabled (php artisan webpush:vapid, once)"

echo "==> Pull"
git pull --ff-only

echo "==> Backend dependencies"
cd "$ROOT/backend"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Migrate"
php artisan down --retry=30 || true
trap 'php artisan up' EXIT
php artisan migrate --force
php artisan storage:link 2>/dev/null || true

echo "==> Caches"
php artisan optimize

php artisan up
trap - EXIT

if [ -f "$ROOT/web/package.json" ]; then
  echo "==> PWA build"
  cd "$ROOT/web"
  npm ci --no-audit --no-fund
  npm run build
  # The build regenerates committed files (theme + public-site tokens/fonts). Any difference would block the next pull.
  git -C "$ROOT" diff --quiet -- web/src/styles/theme.css backend/public/site \
    || echo "WARNING: the build changed committed files; run 'npm run tokens' locally and commit the result"
fi

echo "==> Restart queue workers (systemd restarts them)"
cd "$ROOT/backend"
php artisan queue:restart

echo "==> Done: $(git -C "$ROOT" log -1 --format='%h %s')"
