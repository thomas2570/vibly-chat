FROM php:8.2-apache

# Install required system packages
RUN apt-get update && apt-get install -y \
    zip unzip git supervisor \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache tunneling modules
RUN a2enmod proxy proxy_http proxy_wstunnel rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory where Apache expects files
WORKDIR /var/www/html

# Copy all project files into the container
COPY . .

# ERADICATE any Windows-native vendor folders that were accidentally dragged to GitHub
RUN rm -rf vendor composer.lock

# Run composer to perform a pristine Linux build of Chat WebSocket dependencies
RUN composer install --no-dev --optimize-autoloader

# Create Apache configuration to proxy /ws requests back to Ratchet internally on 8080
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    ProxyPreserveHost On\n\
    RewriteEngine On\n\
    RewriteCond %{HTTP:Upgrade} websocket [NC]\n\
    RewriteRule ^/ws/?(.*) "ws://127.0.0.1:8080/$1" [P,L]\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Setup Supervisor to run both Apache and Ratchet 
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Render.com will look for port 80
EXPOSE 80

# When the container starts, launch Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
