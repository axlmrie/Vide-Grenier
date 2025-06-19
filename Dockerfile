FROM php:8.3-fpm

RUN apt-get update && apt-get install -y nginx

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
RUN apt-get install -y nodejs

RUN npm install -g npm@10.9.0

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo mbstring

# Installer Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN rm composer-setup.php

# Configurer Nginx
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/conf.d/default.conf

# Créer le répertoire pour le projet
RUN mkdir -p /var/www/html

COPY ./web /var/www/html

WORKDIR /var/www/html

RUN /usr/local/bin/composer install

RUN npm install

RUN npm install sass --save-dev

RUN npm run build

EXPOSE 80

CMD bash -c "nginx -g 'daemon off;' & php-fpm"

