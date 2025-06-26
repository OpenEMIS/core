####################################################################################
# Dockerfile
#
# Description     : Multi stage build for OpenEMIS Core using  Angular, PHP
#                   followed by deployment on Apache HTTP Server
# Created         : 2025-06-04
# Version         : 4.28.0
#
# Usage           : docker build .
#
# Notes           :  - Uses Angular CLI 11.2.19 and Node 12.14.1
#                    - Uses PHP 8.3-apache
#                    - Output is served by Apache 2.4
####################################################################################

# === Fist Stage: Frontend Build with Angular ===
# Uses the Node 12.14.1 base image for building Angular frontend application
FROM node:12.14.1 AS frontend-builder

# Sets the working directory inside the container
WORKDIR /app

# Copies the frontend application file
COPY ./frontend/ ./

# Replace the baseUrl
RUN sed -i "s|baseUrl:.*|baseUrl: 'http://localhost:80/core/api/v4'|" ./src/environments/environment.ts
# Install Dependencies
RUN npm install && \
    npm install -g @angular/cli@11.2.19
# Build the Frontend Angular Application
RUN ng build --base-href /core/ --output-path=./dist

# === Second Stage: PHP Backed + Apache Stage ===
# Uses the php image with apache as base image
FROM php:8.3-apache AS backend

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev unzip \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev vim \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip gd pdo pdo_mysql mysqli mbstring xml bcmath

# Copies the apache configuration to container
COPY ./docker-config/apache2/000-default.conf /etc/apache2/sites-available/000-default.conf

# Starts the rewrite and header libraries of apache and deletes the default-ssl.conf configuration file
RUN a2enmod rewrite headers && \
    rm /etc/apache2/sites-available/default-ssl.conf

# Copies the composer binary from it's image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Sets the working directory and copies the application code
WORKDIR /var/www/html/core
COPY . .

# Sets the working directory and update the composer dependencies and autoloads the class file
WORKDIR /var/www/html/core/api
RUN apt install -y git && composer update && composer dump-autoload

# This clears the config, cache, view, route and generate the jwt and key for application and then caches the config
RUN cp .env.example .env && \
    sed -i "s|DB_HOST=.*|DB_HOST=db|g" ./.env &&\
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=root|g" ./.env &&\
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=root|g" ./.env &&\
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear && \
    php artisan route:clear && \
    php artisan key:generate && \
    php artisan jwt:secret && \
    php artisan config:cache

# Sets the Working Direcory
WORKDIR /var/www/html/core

# Copies the frontend application code from previous stage
COPY --from=frontend-builder /app/dist ./webroot/js/angular/dist/

RUN mv ./webroot/js/angular/dist/styles.css ./webroot/css/angular/main/ &&\
    # Creates the log directory
    mkdir -p logs && \
    # Deletes the redundant folder to reduce image size
    rm -rf /var/www/html/core/docker-config /var/www/html/core/frontend &&\
    # Modifies the permission
    chmod -R 777 /var/www/html/core/webroot /var/www/html/core/api/storage /var/www/html/core/logs &&\
    # Modifies ownership
    chown -R www-data:www-data /var/www/html &&\
    # Deletes the apt cache to reduce image size
    rm -rf /var/lib/apt/lists/*
# Container Start Command
CMD ["apache2-foreground"]
