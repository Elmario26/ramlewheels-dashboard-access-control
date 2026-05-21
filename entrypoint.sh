#!/bin/bash
set -e

cd /app

# Generate JWT keys on first run if missing (not committed to git)
if [ ! -f config/jwt/private.pem ]; then
  echo "Generating JWT keys..."
  mkdir -p config/jwt
  openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:2048
  openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
  chown -R www-data:www-data config/jwt
fi

echo "Waiting for database..."
TRIES=0
MAX_TRIES=45
until php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; do
  TRIES=$((TRIES + 1))
  if [ "$TRIES" -ge "$MAX_TRIES" ]; then
    echo "ERROR: Could not connect to database after ${MAX_TRIES} attempts."
    php bin/console doctrine:query:sql "SELECT 1" 2>&1 || true
    exit 1
  fi
  echo "  MySQL not ready yet (${TRIES}/${MAX_TRIES}), retrying in 2s..."
  sleep 2
done
echo "Database is ready."

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Installing assets..."
php bin/console importmap:install --no-interaction 2>/dev/null || true
php bin/console assets:install public --no-interaction

echo "Warming Symfony cache..."
rm -rf var/cache/*
php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
exec nginx -g "daemon off;"
