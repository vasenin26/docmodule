#!/bin/bash
set -e



# 1. Настройка прав
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;

# 2. Миграции + seed (только если база доступна)
if php artisan migrate:status >/dev/null 2>&1; then
    php artisan migrate --force
fi

# 3. Очистка кешей, не трогая таблицу cache, если БД недоступна
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 4. Запуск Supervisor (Nginx + очередь)
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
