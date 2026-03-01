#!/bin/sh

# Run composer install if vendor directory is missing
if [ ! -f "/var/www/vendor/autoload.php" ]; then
    echo "vendor/autoload.php not found, running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --working-dir=/var/www
fi

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