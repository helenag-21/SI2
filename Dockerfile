FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<\/VirtualHost>/i\\t<Directory /var/www/html>\n\t\tAllowOverride All\n\t\tOptions -Indexes\n\t\tRequire all granted\n\t</Directory>' /etc/apache2/sites-available/000-default.conf

RUN echo "upload_max_filesize = 25M\npost_max_size = 30M" > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
