FROM php:8.2-apache

# Instalar extensión para PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql

# Copiar archivos
COPY . /var/www/html/
