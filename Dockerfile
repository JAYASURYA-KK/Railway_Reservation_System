FROM php:8.2-apache

# Install mysqli
RUN docker-php-ext-install mysqli

# Set Apache to use Render PORT
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Copy project files
COPY . /var/www/html/

# Enable rewrite
RUN a2enmod rewrite

# Fix ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
