FROM php:8.2-apache

# Instalar extensiones de MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar todo el código
COPY . /var/www/html/

# Crear directorios de uploads y asignar permisos
RUN mkdir -p /var/www/html/uploads/km_inicio \
    && mkdir -p /var/www/html/uploads/km_fin \
    && mkdir -p /var/www/html/uploads/hotel \
    && mkdir -p /var/www/html/uploads/caseta \
    && mkdir -p /var/www/html/uploads/comida \
    && mkdir -p /var/www/html/uploads/estacionamiento \
    && mkdir -p /var/www/html/uploads/gasolina \
    && mkdir -p /var/www/html/uploads/otros \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

EXPOSE 80
