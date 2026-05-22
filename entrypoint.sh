#!/bin/bash
set -e

cd /app

# Railway / Docker: Symfony needs DATABASE_URL; compose injects it, Railway often only exposes MYSQL_*.
ensure_database_url() {
  if [ -n "${DATABASE_URL:-}" ]; then
    return 0
  fi

  if [ -n "${MYSQL_URL:-}" ]; then
    export DATABASE_URL="${MYSQL_URL}"
  elif [ -n "${MYSQLHOST:-}" ] && [ -n "${MYSQLUSER:-}" ] && [ -n "${MYSQLPASSWORD:-}" ]; then
    local db_port="${MYSQLPORT:-3306}"
    local db_name="${MYSQLDATABASE:-${MYSQL_DATABASE:-railway}}"
    export DATABASE_URL="mysql://${MYSQLUSER}:${MYSQLPASSWORD}@${MYSQLHOST}:${db_port}/${db_name}"
  fi

  if [ -z "${DATABASE_URL:-}" ]; then
    echo "ERROR: DATABASE_URL is not set."
    echo "  Railway: open your app service → Variables → add a reference from the MySQL plugin, e.g."
    echo "    DATABASE_URL = \${{MySQL.MYSQL_URL}}"
    echo "  Or set MYSQL_URL / MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE on this service."
    exit 1
  fi

  case "${DATABASE_URL}" in
    *serverVersion=*) ;;
    *\?*)
      export DATABASE_URL="${DATABASE_URL}&serverVersion=8.0.32&charset=utf8mb4"
      ;;
    *)
      export DATABASE_URL="${DATABASE_URL}?serverVersion=8.0.32&charset=utf8mb4"
      ;;
  esac
}

ensure_database_url

# Generate JWT keys on first run if missing (not committed to git)
if [ ! -f config/jwt/private.pem ]; then
  echo "Generating JWT keys..."
  mkdir -p config/jwt
  openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:2048 2>/dev/null
  openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem 2>/dev/null
  chown -R www-data:www-data config/jwt
fi

echo "Waiting for database..."
TRIES=0
MAX_TRIES=45
until php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; do
  TRIES=$((TRIES + 1))
  if [ "$TRIES" -ge "$MAX_TRIES" ]; then
    echo "ERROR: Could not connect to database after ${MAX_TRIES} attempts."
    php bin/console dbal:run-sql "SELECT 1" 2>&1 || true
    exit 1
  fi
  echo "  MySQL not ready yet (${TRIES}/${MAX_TRIES}), retrying in 2s..."
  sleep 2
done
echo "Database is ready."

echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Provisioning admin user (if configured)..."
php bin/console app:provision-admin --no-interaction 2>/dev/null || true

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
