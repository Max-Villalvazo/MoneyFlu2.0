FROM php:8.2-apache

# Habilitar mod_rewrite (para que las rutas de la API funcionen bonito)
RUN a2enmod rewrite headers

# Instalar extensiones de PHP necesarias (PDO para MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Copiar configuración personalizada de Apache (apunta el DocumentRoot a /public)
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Configurar sesiones de PHP: importante para que las cookies de sesión
# funcionen bien en peticiones cross-origin durante desarrollo con Vite
RUN { \
        echo 'session.cookie_samesite = "Lax"'; \
        echo 'session.cookie_httponly = 1'; \
        echo 'display_errors = On'; \
        echo 'error_reporting = E_ALL'; \
    } > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

EXPOSE 80
