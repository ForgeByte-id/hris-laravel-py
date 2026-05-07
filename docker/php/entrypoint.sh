#!/bin/sh
set -e

cd /var/www/html

# Clear cached config so docker-compose environment variables are always live.
# Without this, a previously generated bootstrap/cache/config.php would
# hard-code the values from the last build and ignore the running environment.
if [ -f bootstrap/cache/config.php ]; then
    php artisan config:clear
fi

exec "$@"
