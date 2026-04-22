# 1. Usamos una imagen base de PHP con Apache
FROM php:8.2-apache

# 2. --- SOLUCIÓN DEFINITIVA ---
# Actualizamos la lista de paquetes, instalamos la librería de PostgreSQL (libpq-dev)
# y LUEGO instalamos las extensiones de PHP para PostgreSQL (pdo_pgsql y pgsql)
# El "rm -rf /var/lib/apt/lists/*" es solo para limpiar y que la imagen pese menos.
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# 3. Copiamos todo tu código (index.php, css, js, api) al contenedor
COPY . /var/www/html/

# 4. (Opcional pero recomendado) Configuramos permisos para que Apache pueda leer los archivos
RUN chown -R www-data:www-data /var/www/html
