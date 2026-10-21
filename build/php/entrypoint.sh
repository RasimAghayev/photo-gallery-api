#!/bin/sh
# The entry point of the application container.
#
# The state of the stack is a mount of the machine, so its permissions do not
# belong to the user of the container and its directories are not the ones of
# the image. The entry point creates them and hands them over, so the workers
# of the web server can write there.
#
# The key of the application is read from the environment. When the
# environment does not carry one, a random key is generated for this
# container, so the application starts either way.
set -e

APP_HOME=/var/www/html

if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
    echo "app: no APP_KEY was given, a random one was generated for this container"
fi

mkdir -p \
    "$APP_HOME/storage/app/private" \
    "$APP_HOME/storage/app/public" \
    "$APP_HOME/storage/framework/cache/data" \
    "$APP_HOME/storage/framework/sessions" \
    "$APP_HOME/storage/framework/testing" \
    "$APP_HOME/storage/framework/views" \
    "$APP_HOME/storage/logs" \
    "$APP_HOME/bootstrap/cache"

chown -R www-data:www-data "$APP_HOME/storage" "$APP_HOME/bootstrap/cache" 2>/dev/null || true

exec "$@"
