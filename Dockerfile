FROM node:20-bookworm AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci || npm install

COPY webpack.config.js ./
COPY assets ./assets
RUN npm run build


FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache gd mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var public

RUN cat > /etc/apache2/sites-available/000-default.conf <<'APACHE'
<VirtualHost *:80>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
APACHE

EXPOSE 8080

CMD sh -c 'sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf && sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-8080}>/" /etc/apache2/sites-available/000-default.conf && php bin/console cache:clear --env=prod --no-warmup || true && apache2-foreground'
