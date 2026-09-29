#!/bin/sh
# Inicia o Apache na porta que o Render injeta via $PORT (padrão 10000).
set -e
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground
