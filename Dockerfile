# Dockerfile pour application PHP + Apache avec installation des extensions
FROM php:8.2-apache-bookworm


# Installer les dépendances système nécessaires à la compilation des extensions PHP
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip gd \
    && php -m

RUN a2enmod rewrite

COPY . /var/www/html/

# Copier explicitement le fichier .env pour que les variables d'environnement soient disponibles
COPY .env /var/www/html/.env

# Charger les variables d'environnement depuis .env dans le système
RUN echo "DATABASE_URL=$(grep DATABASE_URL /var/www/html/.env | cut -d'=' -f2)" >> /etc/environment

# Créer le répertoire uploads s'il n'existe pas
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads

# Configuration Apache pour permettre l'utilisation de .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Ajouter ServerName pour supprimer l'avertissement
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Le port à utiliser
EXPOSE 80

# Démarrage Apache
CMD ["apache2-foreground"]
