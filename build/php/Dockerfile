# The application image of photo-gallery-api.
#
# The base images are pinned by digest: a floating tag would let the runtime
# drift away from the version the lock file was built for. The application
# source is copied into the image, so the container needs no bind mount of the
# repository. Every bind mount of the compose stack lives under var/ (R92).

FROM php:8.5-apache@sha256:4cc63b3d4e05c40da73e7af5d39364ca2678ac07208919497bda3bbab975be95 AS base

# The application talks to the database with pdo_mysql. The extension gd is
# needed by the tests, which build the image of a test file.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql zip gd \
    && rm -rf /var/lib/apt/lists/*

# The routing of Laravel needs the rewrite module: every request that is not
# a file of the document root is sent to the front controller. The module is
# not enabled in the base image.
RUN a2enmod rewrite

# The composer of the matching version, so the lock file is installed the way
# it was resolved.
COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# The dependency layer. The lock file alone decides the tree, so the layer is
# reused whenever only the application code changes.
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --prefer-dist

# The runtime image installs only the production dependencies.
FROM base AS vendor-prod

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --no-dev --prefer-dist

# The test image carries the suite next to the application. The suite runs
# against an in memory database, so it needs neither a database nor a
# network.
FROM vendor AS test

COPY . .
RUN composer dump-autoload --optimize --no-interaction --no-scripts \
    && chown -R www-data:www-data /var/www/html

USER www-data

ENTRYPOINT ["./vendor/bin/phpunit", "--colors=always"]

# The runtime image carries the application only. The process runs as the
# unprivileged www-data user, and the image holds no secret.
FROM vendor-prod AS runtime

# The web server listens on a port that needs no privilege. The process file
# and the lock directory live in /tmp, which every user owns.
#
# The master process of the web server runs as root, which is the
# configuration of the base image: it needs the privilege to write its log
# into the output of the container. Every request is answered by a worker of
# the unprivileged user www-data, and the application never runs as root.
ENV APACHE_LOCK_DIR=/tmp \
    APACHE_RUN_DIR=/tmp \
    APACHE_PID_FILE=/tmp/apache2.pid
COPY build/php/apache.conf /etc/apache2/ports.conf
COPY build/php/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-interaction --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/private storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chmod +x build/php/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

# The container starts as root, because the state of the stack is a mount of
# the machine and its permissions have to be handed over. The entry point
# drops the privilege before the application starts, so the application
# itself runs as the unprivileged user www-data.
EXPOSE 8080

ENTRYPOINT ["build/php/entrypoint.sh"]
CMD ["apache2-foreground"]

# The application answers the listing of the gallery without a body. The check
# asks for it, so a running process and a dead one can be told apart.
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=15s \
  CMD php -r "exit(@fsockopen('127.0.0.1', 8080) ? 0 : 1);"
