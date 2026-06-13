#!/bin/sh

set -e

echo "============================================"
echo " Laravel Entrypoint"
echo "============================================"

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Esperar base de datos
echo "[1/4] Esperando conexión a la base de datos..."

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
MAX_RETRIES=30
attempt=0

until php -r "
    try {
        new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');
        echo 'ok';
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null | grep -q "ok"; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge "$MAX_RETRIES" ]; then
        echo "ERROR: No se pudo conectar a la base de datos."
        exit 1
    fi
    echo "  → Intento ${attempt}/${MAX_RETRIES}..."
    sleep 2
done

echo "  ✓ Base de datos lista."

echo "[2/4] Limpiando caches..."
php artisan config:clear || true
php artisan cache:clear || true

echo "[3/4] Ejecutando migraciones..."
php artisan migrate --seed --force

echo "[4/4] Generando caches de producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "============================================"
echo " Iniciando PHP-FPM..."
echo "============================================"

exec "$@"