FROM eu.gcr.io/nektria/php:8.3
USER root
WORKDIR /app
RUN unset APP_ENV

ENV PATH ~/.composer/vendor/bin:$PATH

RUN composer global require phpstan/phpstan phpstan/phpstan-mockery phpstan/phpstan-phpunit phpstan/phpstan-strict-rules phpstan/phpstan-symfony phpstan/phpstan-doctrine shipmonk/phpstan-rules spaze/phpstan-disallowed-calls
RUN composer global require friendsofphp/php-cs-fixer:dev-master
RUN composer global require squizlabs/php_codesniffer
RUN composer global require phpunit/phpunit

ADD server/__PROJECT__-dev/php.ini /etc/php83/conf.d/nektria_custom.ini
COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock
COPY symfony.lock /app/symfony.lock
COPY public /app/public

RUN composer install

CMD sh server/__PROJECT__-dev/web.sh
