FROM php:8.2-apache

# Instalar Python
RUN apt-get update && apt-get install -y python3 python3-pip && rm -rf /var/lib/apt/lists/*

# Instalar dependencias Python
COPY requirements.txt .
RUN pip3 install -r requirements.txt

# Instalar extensión PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo pdo_pgsql

RUN a2enmod rewrite
RUN echo "date.timezone = America/Lima" >> /usr/local/etc/php/conf.d/timezone.ini

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
