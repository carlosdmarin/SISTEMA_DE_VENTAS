FROM php:8.2-apache

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    python3 \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_pgsql

# Instalar librerías Python (sin --break-system-packages)
RUN pip3 install supabase pytz

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar zona horaria
RUN echo "date.timezone = America/Lima" >> /usr/local/etc/php/conf.d/timezone.ini

# Copiar archivos
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
