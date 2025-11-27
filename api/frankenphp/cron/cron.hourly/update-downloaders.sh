#!/bin/bash

php /app/bin/console core:downloaders:update >> /var/log/update-downloaders.log 2>&1
if [ $? -ne 0 ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') ERROR: core:downloaders:update failed" >> /var/log/update-downloaders.log
fi
