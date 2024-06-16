#!/bin/bash

cd /app
unset APP_ENV
bin/console > /dev/null
php-fpm83 -D && sudo nginx
cd -
