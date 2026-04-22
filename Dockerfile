FROM php:8.2-apache

# Instalar dependencias y extensión PostgreSQL (por si acaso)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    python3 \
    python3-pip \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Instalar la librería de Supabase para Python
RUN pip3 install supabase --break-system-packages

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar archivos
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
