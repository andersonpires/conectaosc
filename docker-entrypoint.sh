#!/bin/bash
set -e

mkdir -p /var/www/html/conectaosc/api/cron/logs
chown -R www-data:www-data /var/www/html/conectaosc/api/cron/logs

service cron start

exec apache2-foreground
