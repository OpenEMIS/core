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

# POCOR-9594: URL substitution removed — ApiService now derives base URL dynamically
# from window.location.origin at runtime, so no build-time URL baking needed.
RUN cp ./src/environments/environment.ts_default ./src/environments/environment.ts &&\
    cp ./src/environments/environment.prod.ts_default ./src/environments/environment.prod.ts

# Install Dependencies
RUN npm install && \
    npm install -g @angular/cli@11.2.19

    # Build the Frontend Angular Application
RUN ng build --base-href /core/ --output-path=./dist

# === Second Stage: PHP Backed + Apache Stage ===
# Uses the php image with apache as base image
FROM php:8.4-apache AS backend
# POCOR-9694: pinned to 8.4 to restore composer resolution on hosts running PHP 8.5

# Install system dependencies
# POCOR-9694: added `cron` for the OpenEMIS Runtime single-cron entry-point
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev unzip git inotify-tools cron \
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
RUN composer config --global process-timeout 1200 && rm composer.lock && composer install --prefer-source --no-progress --no-interaction && composer dump-autoload

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

COPY ./docker-config/init.sh /usr/bin/init.sh

# POCOR-9694: install OpenEMIS Runtime crontab (runs as www-data, every minute)
COPY ./docker-config/cron/openemis-core /etc/cron.d/openemis-core
RUN chmod 0644 /etc/cron.d/openemis-core

RUN chmod 755 /usr/bin/init.sh

# Container Start Command
CMD ["/usr/bin/init.sh"]
