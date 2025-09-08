#!/usr/bin/env bash
set -euo pipefail

# Wait for DB if configured
if [ -n "${DB_HOST:-}" ] && [ -n "${DB_PORT:-}" ]; then
  echo "Waiting for database ${DB_HOST}:${DB_PORT}..."
  for i in {1..60}; do
    if (echo > "/dev/tcp/${DB_HOST}/${DB_PORT}") >/dev/null 2>&1; then
      echo "Database is up"
      break
    fi
    echo "... still waiting (${i})"
    sleep 1
  done
fi

cd /var/www/html

# Ensure key exists
if [ ! -f storage/oauth-private.key ] && [ -f artisan ]; then
  php artisan key:generate --force || true
fi

# Run migrations
if [ -f artisan ]; then
  php artisan migrate --force || true
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf


