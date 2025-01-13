FROM eu.gcr.io/nektria/php:8.3
USER root
WORKDIR /app
RUN unset APP_ENV

ENV PATH ~/.composer/vendor/bin:$PATH

RUN apk add php-yaml

RUN composer global require phpstan/phpstan phpstan/phpstan-mockery phpstan/phpstan-phpunit phpstan/phpstan-strict-rules phpstan/phpstan-symfony phpstan/phpstan-doctrine shipmonk/phpstan-rules spaze/phpstan-disallowed-calls ergebnis/phpstan-rules
RUN composer global require friendsofphp/php-cs-fixer:dev-master
RUN composer global require squizlabs/php_codesniffer
RUN composer global require phpunit/phpunit

ADD server/__PROJECT__-dev/php.ini /etc/php83/conf.d/nektria_custom.ini
COPY . /app

RUN composer install
RUN bin/console debug:setup:assets -q

CMD sh server/__PROJECT__-dev/web.sh
