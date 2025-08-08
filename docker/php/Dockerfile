FROM debian:bookworm

# Add PHP repository
RUN apt-get update && apt-get install -y software-properties-common ca-certificates lsb-release apt-transport-https wget
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# install php 8.2 cli
RUN apt-get update && \
    apt-get install -y \
        php8.2-cli \
        php8.2-fpm \
        php8.2-gd \
        php8.2-mbstring \
        php8.2-pdo \
        php8.2-mysql \
        php8.2-pgsql \
        php8.2-zip \
        php8.2-xdebug \
        php8.2-intl \
        php8.2-dom \
        php8.2-xml \
        php8.2-curl \
        php8.2-bcmath \
        php8.2-fileinfo

# Install system dependencies required for Laravel 12
RUN apt-get update && \
    apt-get install -y \
        git \
        unzip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        zip \
        curl \
        libzip-dev \
        libpq-dev \
        libicu-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libssl-dev

#install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#install nodejs vesrion 22.10.0
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs


RUN groupadd -g 1000 local && \
    useradd -m -u 1000 -g local local

WORKDIR /app