# --------------------------------------------
# Base runtime image
# --------------------------------------------
FROM debian:bookworm AS base-runtime

ENV DEBIAN_FRONTEND=noninteractive \
    APP_ENV=local \
    APP_DEBUG=true

RUN apt-get update && apt-get install -y \
        software-properties-common \
        ca-certificates \
        lsb-release \
        apt-transport-https \
        wget \
        gnupg2 \
        git \
        unzip \
        zip \
        curl \
        nginx \
        supervisor \
        libzip-dev \
        libpq-dev \
        libicu-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libssl-dev && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && apt-get install -y \
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
        php8.4-sqlite3 \
        libzip-dev \
        libpq-dev \
        libicu-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libssl-dev && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# --------------------------------------------
# Builder stage: composer + node
# --------------------------------------------
FROM base-runtime AS builder

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get update && apt-get install -y nodejs && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# --------------------------------------------
# Vendor stage: prod PHP dependencies
# --------------------------------------------
FROM builder AS vendor

COPY app/composer.json app/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

# --------------------------------------------
# Frontend stage: build Vite/Inertia
# --------------------------------------------
FROM builder AS frontend

COPY app/package.json app/package-lock.json* ./
RUN npm ci --no-audit --no-fund

COPY app/ .
ENV APP_ENV=production APP_DEBUG=false
RUN npm run build

# --------------------------------------------
# Production image
# --------------------------------------------
FROM base-runtime AS prod

ENV APP_ENV=production APP_DEBUG=false

WORKDIR /var/www/html

# Copy PHP app
COPY --from=vendor /var/www/html/vendor ./vendor
COPY app/ .
RUN mv .env.prod .env

# Copy built frontend
COPY --from=frontend /var/www/html/public/build ./public/build

# Nginx + Supervisor
COPY docker/nginx/sites/laravel.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    mkdir -p storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    find storage -type d -exec chmod 775 {} \; || true && \
    find storage -type f -exec chmod 664 {} \; || true && \
    chmod -R 775 bootstrap/cache && \
    rm -f /var/www/html/public/hot

EXPOSE 80
CMD ["/entrypoint.sh"]

# --------------------------------------------
# Development image
# --------------------------------------------
FROM base-runtime AS dev

ENV APP_ENV=local APP_DEBUG=true

WORKDIR /app

RUN apt-get update && apt-get install -y php8.4-xdebug && rm -rf /var/lib/apt/lists/*

# Composer + Node
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get update && apt-get install -y nodejs && rm -rf /var/lib/apt/lists/*

# Local user
RUN groupadd -g 1000 local && useradd -m -u 1000 -g local local
USER local

WORKDIR /app
EXPOSE 80 8000 5173

CMD ["bash", "-lc", "composer run dev"]
