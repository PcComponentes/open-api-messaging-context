FROM php:8.0-cli-alpine3.14

RUN apk update && \
    apk add --no-cache \
        libzip-dev \
        git \
        openssl-dev && \
    docker-php-ext-install -j$(nproc) \
        zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

ENV PATH /var/app/vendor/bin:$PATH

WORKDIR /var/app
