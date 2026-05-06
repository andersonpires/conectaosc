#!/bin/bash
set -e

mkdir -p /var/www/html/conectaosc/api/cron/logs
chown -R www-data:www-data /var/www/html/conectaosc/api/cron/logs

# Garante que o diretório de fotos existe e tem permissão correta,
# mesmo quando montado como volume vazio pelo orquestrador.
FOTOS_DIR=/var/www/html/conectaosc/app/assets/img/fotos
BACKUP_DIR=/var/lib/conectaosc/fotos-backup

mkdir -p "$FOTOS_DIR"

if [ -d "$BACKUP_DIR" ]; then
    if [ -z "$(ls -A "$FOTOS_DIR" 2>/dev/null)" ]; then
        # Volume vazio (primeira montagem): restaura todas as fotos da imagem
        cp -r "$BACKUP_DIR/." "$FOTOS_DIR/"
    else
        # Volume já tem conteúdo: garante apenas as fotos padrão
        for f in padrao.jfif padrao.jpg; do
            if [ ! -f "${FOTOS_DIR}/${f}" ] && [ -f "${BACKUP_DIR}/${f}" ]; then
                cp "${BACKUP_DIR}/${f}" "${FOTOS_DIR}/${f}"
            fi
        done
    fi
fi

chown -R www-data:www-data "$FOTOS_DIR"

service cron start

exec apache2-foreground
