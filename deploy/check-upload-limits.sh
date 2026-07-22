#!/usr/bin/env bash
# Quick diagnose: what actually limits uploads on this host?
#   bash deploy/check-upload-limits.sh

set -euo pipefail

echo "=== PHP binaries / services ==="
php -v 2>/dev/null | head -n1 || echo "php missing"
command -v nginx || true
command -v apache2 || true
command -v httpd || true
systemctl list-units --type=service --all --no-pager 2>/dev/null | grep -iE 'php|nginx|apache|httpd|caddy' || true
echo

echo "=== php.ini files ==="
find /etc/php /etc -name 'php.ini' 2>/dev/null | sort || true
echo

echo "=== CLI limits (NOT used by browser uploads) ==="
php -r 'echo "upload_max_filesize=".ini_get("upload_max_filesize")."\npost_max_size=".ini_get("post_max_size")."\n";' 2>/dev/null || true
echo

echo "=== FPM / Apache limits ==="
for bin in php-fpm php-fpm8.5 php-fpm8.4 php-fpm8.3 php-fpm8.2; do
  if command -v "$bin" >/dev/null 2>&1; then
    echo "-- $bin -i --"
    "$bin" -i 2>/dev/null | grep -E '^upload_max_filesize|^post_max_size' || true
  fi
done
echo

echo "=== Nginx client_max_body_size ==="
grep -RIn --include='*.conf' 'client_max_body_size' /etc/nginx 2>/dev/null || echo "(none found)"
echo

echo "=== App .env ==="
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ -f "$ROOT/.env" ]]; then
  grep -E '^UPLOAD_MAX_FILE_KB=' "$ROOT/.env" || echo "UPLOAD_MAX_FILE_KB missing in $ROOT/.env"
else
  echo ".env not found at $ROOT/.env"
fi
echo

echo "=== Tip ==="
echo "Browser uploads use FPM/Apache php.ini + Nginx client_max_body_size + UPLOAD_MAX_FILE_KB."
echo "If /etc/php/*/fpm does not exist: install php-fpm, then re-run raise-upload-limits.sh"
