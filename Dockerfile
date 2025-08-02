# Use the official PHP with Apache image
FROM php:8.3-apache

# Enable Apache mod_rewrite (if needed)
RUN a2enmod rewrite

# Copy all source code to the web root
COPY public/ /var/www/html/

# Create a writable sessions folder
RUN mkdir -p /var/www/html/data/sessions \
    && chown -R www-data:www-data /var/www/html/data

# Set proper permissions
RUN chmod -R 775 /var/www/html/data

# Expose port 80 (Apache)
EXPOSE 80
