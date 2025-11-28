FROM php:7.3-apache

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем PHP расширения
RUN docker-php-ext-install mysqli pdo pdo_mysql zip

# Устанавливаем ionCube Loader
RUN cd /tmp \
    && curl -O https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz \
    && tar zxvf ioncube_loaders_lin_x86-64.tar.gz \
    && PHP_EXT_DIR=$(php -r "echo ini_get('extension_dir');") \
    && cp ioncube/ioncube_loader_lin_7.3.so $PHP_EXT_DIR/ \
    && echo "zend_extension=$PHP_EXT_DIR/ioncube_loader_lin_7.3.so" > /usr/local/etc/php/conf.d/00-ioncube.ini \
    && rm -rf ioncube_loaders_lin_x86-64.tar.gz ioncube

# Включаем mod_rewrite для Apache
RUN a2enmod rewrite

# Устанавливаем правильную директорию для Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf