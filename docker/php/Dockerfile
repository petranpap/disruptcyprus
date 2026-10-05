# Development image for the Laravel backend (php-fpm, queue worker, scheduler).
FROM docker.io/library/php:8.5-fpm-trixie

ARG UID=1000
ARG GID=1000

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip curl \
        libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype-dev \
        jpegoptim optipng pngquant \
    && rm -rf /var/lib/apt/lists/*

# Low parallelism keeps peak memory small on modest dev machines.
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j2 pdo_mysql zip gd bcmath exif pcntl

RUN docker-php-ext-install -j2 intl

RUN pecl install redis && docker-php-ext-enable redis && rm -rf /tmp/pear

COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY fpm.conf /usr/local/etc/php-fpm.d/zz-host-network.conf

# Run as a user matching the host user so bind-mounted files keep sane ownership.
RUN groupadd -g "${GID}" app && useradd -u "${UID}" -g app -m -s /bin/bash app

WORKDIR /var/www/html
USER app
