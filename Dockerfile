FROM php:8.2-apache

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install database extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite module for .htaccess routing
RUN a2enmod rewrite

# Allow Apache to read and respect .htaccess files
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy your application files
COPY . /var/www/html/

# Install PHP dependencies
RUN cd /var/html && composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Set proper permissions for the web server
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
