#!/bin/bash

cd /app
unset APP_ENV
bin/console > /dev/null
bin/console messenger:consume --time-limit=3600 -v -b messenger.bus.default --all
cd -
