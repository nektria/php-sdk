FROM eu.gcr.io/nektria/php:8.3

ENV APP_ENV=prod

COPY . /app/
RUN chown www-data:www-data /app

RUN export $(grep -v '^#' /app/.env | xargs) && \
    APP_ENV=prod composer install --no-dev --no-interaction -o

RUN composer dump-autoload --no-dev --classmap-authoritative

USER www-data

