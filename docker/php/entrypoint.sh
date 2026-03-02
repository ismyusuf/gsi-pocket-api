#!/bin/sh

# Copy .env from example if it doesn't exist
if [ ! -f "/var/www/.env" ]; then
    echo ".env not found, copying from .env.example..."
    cp /var/www/.env.example /var/www/.env
fi

# Run composer install if vendor directory is missing
if [ ! -f "/var/www/vendor/autoload.php" ]; then
    echo "vendor/autoload.php not found, running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --working-dir=/var/www
fi

# Generate APP_KEY if empty
APP_KEY=$(grep '^APP_KEY=' /var/www/.env | cut -d '=' -f2)
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php /var/www/artisan key:generate --force
fi

# Generate JWT_SECRET if empty
JWT_SECRET=$(grep '^JWT_SECRET=' /var/www/.env | cut -d '=' -f2)
if [ -z "$JWT_SECRET" ]; then
    echo "Generating JWT_SECRET..."
    php /var/www/artisan jwt:secret --force
fi

# Run database migrations
echo "Running migrations..."
php /var/www/artisan migrate --force

# Fix Laravel permissions if folders exist
if [ -d "/var/www/storage" ]; then
    chown -R www-data:www-data /var/www/storage
    chmod -R 775 /var/www/storage
fi

if [ -d "/var/www/bootstrap/cache" ]; then
    chown -R www-data:www-data /var/www/bootstrap/cache
    chmod -R 775 /var/www/bootstrap/cache
fi

exec "$@"

exec "$@"