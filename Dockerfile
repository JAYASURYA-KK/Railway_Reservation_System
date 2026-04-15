FROM php:8.2-apache

# Enable mysqli extension
RUN docker-php-ext-install mysqli

# Copy project files
COPY . /var/www/html/

# Enable Apache rewrite (optional)
RUN a2enmod rewrite
