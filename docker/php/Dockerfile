FROM debian:bookworm AS base-runtime

ENV DEBIAN_FRONTEND=noninteractive \
    APP_ENV=local \
    APP_DEBUG=true

RUN apt-get update && apt-get install -y software-properties-common ca-certificates lsb-release apt-transport-https wget gnupg2 && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && \
    apt-get install -y \
        git \
        unzip \
        zip \
        curl \
        nginx \
        supervisor \
        php8.4-cli \
        php8.4-fpm \
        php8.4-gd \
        php8.4-mbstring \
        php8.4-pdo \
        php8.4-pgsql \
        php8.4-zip \
        php8.4-intl \
        php8.4-dom \
        php8.4-xml \
        php8.4-curl \
        php8.4-bcmath \
        php8.4-fileinfo \
        libzip-dev \
        libpq-dev \
        libicu-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libssl-dev && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM base-runtime AS builder
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get update && apt-get install -y nodejs && \
    rm -rf /var/lib/apt/lists/*

FROM builder AS vendor
WORKDIR /var/www/html
COPY app/ ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

FROM builder AS frontend
WORKDIR /var/www/html
COPY app/package.json app/package-lock.json* ./
RUN if [ -f package.json ]; then npm ci --no-audit --no-fund; fi
COPY app/ .
RUN if [ -f package.json ]; then npm run build; fi

FROM base-runtime AS prod
ENV APP_ENV=production APP_DEBUG=false
WORKDIR /var/www/html
COPY app/ /var/www/html/
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --from=frontend /var/www/html/public/build /var/www/html/public/build
COPY docker/nginx/sites/laravel.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    mkdir -p storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    find storage -type d -exec chmod 775 {} \; || true && \
    find storage -type f -exec chmod 664 {} \; || true && \
    chmod -R 775 bootstrap/cache
EXPOSE 80
CMD ["/entrypoint.sh"]

FROM base-runtime AS dev
ENV APP_ENV=local APP_DEBUG=true
WORKDIR /app
RUN apt-get update && apt-get install -y php8.4-xdebug && rm -rf /var/lib/apt/lists/* && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get update && apt-get install -y nodejs && rm -rf /var/lib/apt/lists/* && \
    groupadd -g 1000 local && useradd -m -u 1000 -g local local
USER local
WORKDIR /app
EXPOSE 80 8000 5173
CMD ["bash", "-lc", "composer run dev"]