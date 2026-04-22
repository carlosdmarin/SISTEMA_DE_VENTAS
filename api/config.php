FROM php:8.2-apache

# Instalar PostgreSQL y extensiones
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# Copiar archivos
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html
