FROM composer:1.9.1 as dependencies
WORKDIR /deps
COPY composer.json composer.lock ./
RUN composer install \
    --ignore-platform-reqs \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist \
    ;

FROM php:7.4.0-cli-alpine as env
RUN docker-php-ext-install \
    sockets

WORKDIR /app
COPY --from=dependencies /deps/vendor ./vendor
COPY . .
RUN vendor/bin/generate_endpoint_list
RUN ENVIRONMENT=build bin/compile
CMD php index.php
