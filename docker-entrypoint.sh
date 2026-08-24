#!/bin/sh
# Render injects $PORT and expects the container to listen on it.
# Apache's default image listens on 80, so rewrite that at startup only.
set -e

PORT="${PORT:-80}"

sed -ri "s/^Listen 80\$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec "$@"
