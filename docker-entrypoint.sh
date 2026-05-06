#!/bin/bash
set -e

mkdir -p /var/www/html/conectaosc/api/cron/logs
chown -R www-data:www-data /var/www/html/conectaosc/api/cron/logs

# Garante que o diretório de fotos existe e tem permissão correta,
# mesmo quando montado como volume vazio pelo orquestrador.
mkdir -p /var/www/html/conectaosc/app/assets/img/fotos
chown -R www-data:www-data /var/www/html/conectaosc/app/assets/img/fotos

service cron start

exec apache2-foreground
