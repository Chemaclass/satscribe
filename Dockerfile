# syntax=docker/dockerfile:1

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline --no-audit
COPY vite.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm run build

FROM serversideup/php:8.3-cli AS vendor
USER root
# nostr-php signs through paragonie/ecc, which needs gmp. The base image ships
# without it, and Composer only notices at runtime: Nostr login would 500.
RUN install-php-extensions gmp
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
 && composer check-platform-reqs --no-dev

FROM serversideup/php:8.3-fpm-nginx AS run
USER root
RUN install-php-extensions gmp
USER www-data

# AUTORUN runs the migrations and the config/route/view caches on boot. Caching
# has to happen here rather than at build time: the secrets only exist once
# Kamal has written the env file onto the server.
ENV AUTORUN_ENABLED=true \
    PHP_OPCACHE_ENABLE=1

COPY --chown=www-data:www-data --from=vendor /app /var/www/html
COPY --chown=www-data:www-data --from=assets /app/public/build /var/www/html/public/build

# The SQLite file lives under storage/, which is the mounted volume. Creating
# the directory here is what gives a fresh volume the right ownership: Docker
# seeds a new volume from the image, and a path the image lacks arrives owned
# by root, which www-data cannot write.
RUN mkdir -p /var/www/html/storage/database
