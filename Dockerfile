# FinAccChain - minimal Docker image for Render (PHP + Apache).
# No routing changes: DocumentRoot is the existing project root as-is.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:8.2-apache

# Only the extensions this app actually needs:
# pdo_mysql/mysqli for the database layer, mbstring for DomPDF.
RUN docker-php-ext-install pdo pdo_mysql mysqli mbstring

RUN a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && sed -ri "s!ServerAdmin.*!ServerName localhost!g" /etc/apache2/apache2.conf 2>/dev/null || true

WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN mkdir -p uploads/evidence \
    && chown -R www-data:www-data uploads \
    && chmod -R 775 uploads

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
