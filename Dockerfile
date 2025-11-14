FROM php:8.3-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    zip

# Activation du module rewrite pour Symfony
RUN a2enmod rewrite

# Configuration Apache pour Symfony
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Permissions pour le cache et logs Symfony
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
