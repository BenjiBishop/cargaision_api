# Dockerfile corrigé
FROM php:8.3-apache

# Installation des extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql

# Activation des modules Apache nécessaires
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod deflate

# Configuration du DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# Configuration Apache pour AllowOverride
RUN echo '<Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    AllowOverride All' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Installation de Composer (optionnel pour des dépendances futures)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copie du code source
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Configuration PHP
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/docker-php-memory-limit.ini
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini
RUN echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/docker-php-uploads.ini
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-dev.ini
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-dev.ini

# Script de santé
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost/api/health || exit 1

EXPOSE 80