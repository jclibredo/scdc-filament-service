#!/bin/bash
cd /home/u164809439/domains/scdc-web-app.com/laravel-app
/usr/bin/php artisan queue:work --stop-when-empty --tries=3 >> storage/logs/cron.log 2>&1