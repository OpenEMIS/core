# === Frontend ===
FROM node:12.14.1 AS frontend-builder
WORKDIR /app
COPY ./frontend/ ./
RUN npm install && \
    npm install -g @angular/cli@11.2.19
RUN ng build --output-path=./dist

# === Backed + Apache Stage ===
FROM php:8.3-apache AS backend
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev unzip \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev vim \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip gd pdo pdo_mysql mysqli mbstring xml bcmath
COPY ./docker-config/apache2/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers && \
    rm /etc/apache2/sites-available/default-ssl.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
WORKDIR /var/www/html/api
RUN composer update && composer dump-autoload
RUN php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan route:clear && \
    php artisan key:generate && \
    php artisan jwt:secret && \
    php artisan config:cache
WORKDIR /var/www/html
COPY --from=frontend-builder /app/dist ./webroot/js/angular/dist/
RUN mv ./webroot/js/angular/dist/styles.css ./webroot/css/angular/main/ &&\
    mkdir -p logs && \
    rm -rf /var/www/html/docker-config /var/www/html/frontend &&\
    chmod -R 777 /var/www/html/webroot /var/www/html/api/storage /var/www/html/logs &&\
    chown -R www-data:www-data /var/www/html &&\
    rm -rf /var/lib/apt/lists/* 
CMD ["apache2-foreground"]
