#!/usr/bin/env bash
# Raise ARC transaction upload limits to 400 MB (Nginx + PHP + .env).
# Usage (from project root or anywhere):
#   sudo bash deploy/raise-upload-limits.sh
#   sudo bash deploy/raise-upload-limits.sh /var/www/arc

set -euo pipefail

UPLOAD_MB=400
POST_MB=420
TIMEOUT=600
MEMORY_MB=512
APP_KB=409600

APP_DIR="${1:-}"
if [[ -z "$APP_DIR" ]]; then
  SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
  APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
fi

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run as root: sudo bash $0 ${APP_DIR}"
  exit 1
fi

echo "=== ARC upload limits → ${UPLOAD_MB}M ==="
echo "App dir: $APP_DIR"
echo

set_ini_value() {
  local file="$1" key="$2" value="$3"
  if [[ ! -f "$file" ]]; then
    return 1
  fi
  if grep -qE "^[;[:space:]]*${key}\s*=" "$file"; then
    sed -i -E "s|^[;[:space:]]*${key}\s*=.*|${key} = ${value}|" "$file"
  else
    printf '\n%s = %s\n' "$key" "$value" >> "$file"
  fi
  echo "  updated $key = $value  ($file)"
}

apply_php_ini() {
  local file="$1"
  [[ -f "$file" ]] || return 1
  echo "PHP ini: $file"
  set_ini_value "$file" upload_max_filesize "${UPLOAD_MB}M"
  set_ini_value "$file" post_max_size "${POST_MB}M"
  set_ini_value "$file" max_execution_time "$TIMEOUT"
  set_ini_value "$file" max_input_time "$TIMEOUT"
  set_ini_value "$file" memory_limit "${MEMORY_MB}M"
}

echo "--- Detect stack ---"
systemctl list-units --type=service --all --no-pager 2>/dev/null | grep -iE 'php|nginx|apache|httpd|caddy' || true
echo
ls -la /etc/php 2>/dev/null || echo "(no /etc/php)"
ls -la /etc/php/*/ 2>/dev/null || true
echo

PHP_INIS=()
# Debian/Ubuntu FPM / Apache
while IFS= read -r f; do
  PHP_INIS+=("$f")
done < <(find /etc/php -type f \( -path '*/fpm/php.ini' -o -path '*/apache2/php.ini' \) 2>/dev/null | sort)

# Arch / single php.ini
if [[ -f /etc/php/php.ini ]]; then
  PHP_INIS+=("/etc/php/php.ini")
fi

# Also patch every non-cli php.ini under /etc/php
while IFS= read -r f; do
  case "$f" in
    */cli/php.ini) continue ;;
    *)
      already=0
      for e in "${PHP_INIS[@]:-}"; do
        [[ "$e" == "$f" ]] && already=1 && break
      done
      [[ $already -eq 0 ]] && PHP_INIS+=("$f")
      ;;
  esac
done < <(find /etc/php -name 'php.ini' 2>/dev/null | sort)

if [[ ${#PHP_INIS[@]} -eq 0 ]]; then
  echo "WARNING: No web php.ini found (only CLI may exist)."
  echo "Install PHP-FPM, e.g.:"
  echo "  Ubuntu: sudo apt update && sudo apt install php8.4-fpm"
  echo "  Arch:   sudo pacman -S php-fpm"
  # Still patch CLI so something changes, but warn heavily
  if [[ -f /etc/php/8.4/cli/php.ini ]]; then
    echo "Patching CLI only (will NOT fix browser uploads):"
    apply_php_ini /etc/php/8.4/cli/php.ini || true
  fi
else
  echo "--- Patch PHP ini files ---"
  for f in "${PHP_INIS[@]}"; do
    apply_php_ini "$f" || true
  done
fi
echo

echo "--- Patch Nginx ---"
NGINX_CHANGED=0
if command -v nginx >/dev/null 2>&1 || [[ -d /etc/nginx ]]; then
  CONF_DIR="/etc/nginx"
  TARGETS=()
  [[ -f "$CONF_DIR/nginx.conf" ]] && TARGETS+=("$CONF_DIR/nginx.conf")
  while IFS= read -r f; do
    TARGETS+=("$f")
  done < <(find "$CONF_DIR" -type f \( -name '*.conf' -o -path '*/sites-enabled/*' -o -path '*/conf.d/*' \) 2>/dev/null | sort)

  for conf in "${TARGETS[@]:-}"; do
    [[ -f "$conf" ]] || continue
    if grep -qE '^\s*client_max_body_size\s+' "$conf"; then
      sed -i -E "s|^\s*client_max_body_size\s+[^;]+;|    client_max_body_size ${POST_MB}M;|" "$conf"
      echo "  set client_max_body_size ${POST_MB}M in $conf"
      NGINX_CHANGED=1
    fi
  done

  # Ensure http{} has the directive if nothing found in vhosts
  if [[ $NGINX_CHANGED -eq 0 && -f "$CONF_DIR/nginx.conf" ]]; then
    if grep -qE '^\s*http\s*\{' "$CONF_DIR/nginx.conf"; then
      if ! grep -qE '^\s*client_max_body_size\s+' "$CONF_DIR/nginx.conf"; then
        sed -i -E "s|^(\s*http\s*\{)|\1\n    client_max_body_size ${POST_MB}M;|" "$CONF_DIR/nginx.conf"
        echo "  inserted client_max_body_size ${POST_MB}M into nginx.conf http{}"
        NGINX_CHANGED=1
      fi
    fi
  fi

  # Drop-in snippet for conf.d
  if [[ -d "$CONF_DIR/conf.d" ]]; then
    echo "client_max_body_size ${POST_MB}M;" > "$CONF_DIR/conf.d/arc-upload-limits.conf"
    echo "  wrote $CONF_DIR/conf.d/arc-upload-limits.conf"
    NGINX_CHANGED=1
  fi
else
  echo "  nginx not found — skip"
fi
echo

echo "--- Patch Apache (if present) ---"
if [[ -d /etc/apache2 ]]; then
  cat > /etc/apache2/conf-available/arc-upload-limits.conf <<EOF
# ARC large uploads
LimitRequestBody $((POST_MB * 1024 * 1024))
EOF
  a2enconf arc-upload-limits >/dev/null 2>&1 || true
  echo "  wrote /etc/apache2/conf-available/arc-upload-limits.conf"
elif [[ -d /etc/httpd ]]; then
  echo "  httpd detected — set LimitRequestBody manually if needed"
else
  echo "  apache not found — skip"
fi
echo

echo "--- Patch app .env ---"
ENV_FILE="$APP_DIR/.env"
if [[ -f "$ENV_FILE" ]]; then
  if grep -qE '^UPLOAD_MAX_FILE_KB=' "$ENV_FILE"; then
    sed -i -E "s|^UPLOAD_MAX_FILE_KB=.*|UPLOAD_MAX_FILE_KB=${APP_KB}|" "$ENV_FILE"
  else
    printf '\nUPLOAD_MAX_FILE_KB=%s\n' "$APP_KB" >> "$ENV_FILE"
  fi
  echo "  UPLOAD_MAX_FILE_KB=${APP_KB} in $ENV_FILE"
  if [[ -x "$APP_DIR/artisan" ]]; then
    sudo -u "${SUDO_USER:-www-data}" php "$APP_DIR/artisan" config:clear 2>/dev/null \
      || php "$APP_DIR/artisan" config:clear 2>/dev/null \
      || true
  fi
else
  echo "  WARNING: $ENV_FILE not found — set UPLOAD_MAX_FILE_KB=${APP_KB} manually"
fi
echo

echo "--- Restart services ---"
restarted=0
for svc in php-fpm php8.5-fpm php8.4-fpm php8.3-fpm php8.2-fpm php8.1-fpm; do
  if systemctl list-unit-files --type=service 2>/dev/null | grep -qE "^${svc}\.service"; then
    systemctl restart "$svc" && echo "  restarted $svc" && restarted=1
  elif systemctl cat "$svc" >/dev/null 2>&1; then
    systemctl restart "$svc" && echo "  restarted $svc" && restarted=1
  fi
done

if systemctl cat nginx >/dev/null 2>&1; then
  nginx -t && systemctl reload nginx && echo "  reloaded nginx"
fi
if systemctl cat apache2 >/dev/null 2>&1; then
  systemctl reload apache2 && echo "  reloaded apache2"
fi
if systemctl cat httpd >/dev/null 2>&1; then
  systemctl reload httpd && echo "  reloaded httpd"
fi

if [[ $restarted -eq 0 ]]; then
  echo
  echo "WARNING: No php-fpm service was restarted."
  echo "Install and enable it, then re-run this script:"
  echo "  Ubuntu: sudo apt install php8.4-fpm && sudo systemctl enable --now php8.4-fpm"
  echo "  Arch:   sudo pacman -S php-fpm && sudo systemctl enable --now php-fpm"
fi

echo
echo "--- Verify (must show ${UPLOAD_MB}M / ${POST_MB}M for WEB, not only CLI) ---"
php -r 'echo "CLI  upload_max_filesize=".ini_get("upload_max_filesize")." post_max_size=".ini_get("post_max_size").PHP_EOL;'

# Prefer FPM pool probe via php-fpm -i if available
if command -v php-fpm >/dev/null 2>&1; then
  php-fpm -i 2>/dev/null | grep -E 'upload_max_filesize|post_max_size' | head -n 4 || true
elif command -v php-fpm8.4 >/dev/null 2>&1; then
  php-fpm8.4 -i 2>/dev/null | grep -E 'upload_max_filesize|post_max_size' | head -n 4 || true
elif command -v php-fpm8.3 >/dev/null 2>&1; then
  php-fpm8.3 -i 2>/dev/null | grep -E 'upload_max_filesize|post_max_size' | head -n 4 || true
fi

echo
echo "Done."
echo "Open the transaction create page and check browser DevTools → form data-max-file-bytes."
echo "Expected ≈ $((APP_KB * 1024)) (400 MiB)."
echo "If still low, PHP-FPM is not the SAPI serving the site — check Nginx fastcgi_pass / Apache module."
