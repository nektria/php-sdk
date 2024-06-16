# PHP-SDK

docker exec -i routemanager php -d memory_limit=-1 /root/.composer/vendor/bin/phpstan clear-result-cache
docker exec -i routemanager php -d memory_limit=-1 /root/.composer/vendor/bin/phpstan analyze -c server/routemanager-dev/phpstan.neon --debug

