#!/bin/bash

cd /app
composer install
unset APP_ENV
bin/console > /dev/null
php-fpm83 -D && sudo nginx
cd -
