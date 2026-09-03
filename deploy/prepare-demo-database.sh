#!/usr/bin/env bash
# Prepare ARC database for customer demo — run on the Linux server.
#   cd /var/www/html/arc && sudo bash deploy/prepare-demo-database.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "=== ARC demo database prep ==="
echo "Project: $ROOT"
echo

if [[ ! -f .env ]]; then
  echo "ERROR: .env not found in $ROOT"
  exit 1
fi

echo "=== 1) MySQL service ==="
if systemctl is-active --quiet mysql 2>/dev/null; then
  echo "mysql: active"
elif systemctl is-active --quiet mariadb 2>/dev/null; then
  echo "mariadb: active"
else
  echo "Starting mysql..."
  sudo systemctl start mysql 2>/dev/null || sudo systemctl start mariadb
fi

echo
echo "=== 2) Database connection ==="
php artisan db:show || { echo "ERROR: cannot connect to database. Check DB_* in .env"; exit 1; }

echo
echo "=== 3) Run pending migrations ==="
php artisan migrate --force

echo
echo "=== 4) Verify critical tables ==="
php artisan arc:db-check || true

echo
echo "=== 5) Clear caches ==="
php artisan optimize:clear

echo
echo "=== Done ==="
echo "Open the app in the browser. Default admin (if seeded): admin@arc.local / password"
echo "To load demo data: php artisan db:seed --force"
