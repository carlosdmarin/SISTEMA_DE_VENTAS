FROM php:8.2-apache

# Instalar Python y dependencias
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Forzar instalación de paquetes Python (con --break-system-packages)
RUN pip3 install supabase pytz --break-system-packages

# Instalar extensión PostgreSQL para PHP
RUN docker-php-ext-install pdo pdo_pgsql

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar zona horaria
RUN echo "date.timezone = America/Lima" >> /usr/local/etc/php/conf.d/timezone.ini

# Copiar archivos del proyecto
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
