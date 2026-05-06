#!/bin/bash
set -e

mkdir -p /var/www/html/conectaosc/api/cron/logs
chown -R www-data:www-data /var/www/html/conectaosc/api/cron/logs

# Garante que o diretório de assets existe e tem permissão correta,
# mesmo quando montado como volume vazio pelo orquestrador.
ASSETS_IMG_DIR=/var/www/html/conectaosc/app/assets/img
IMG_BACKUP=/var/lib/conectaosc/img-backup

mkdir -p "$ASSETS_IMG_DIR"

if [ -d "$IMG_BACKUP" ]; then
    if [ -z "$(ls -A "$ASSETS_IMG_DIR" 2>/dev/null)" ]; then
        # Volume vazio (primeira montagem): copia tudo da imagem
        cp -r "$IMG_BACKUP/." "$ASSETS_IMG_DIR/"
    else
        # Volume já tem conteúdo: mescla apenas arquivos novos do deploy
        # sem sobrescrever uploads já existentes no volume (-n = no-clobber)
        cp -rn "$IMG_BACKUP/." "$ASSETS_IMG_DIR/"
    fi
fi

chown -R www-data:www-data "$ASSETS_IMG_DIR"

service cron start

exec apache2-foreground
