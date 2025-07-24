FROM composer:2 AS composer-install

RUN #composer install --optimize-autoloader --no-interaction --no-scripts --ignore-platform-req=ext-grpc --ignore-platform-req=ext-sockets

FROM php:8.3-cli-alpine

RUN apk add --no-cache

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
        autoconf \
        build-base \
    && mkdir -p /tmp/pear/cache \
    && apk add --update --no-cache \
        openssl-dev \
        cmake \
        composer \
        libunwind \
        libunwind-dev \
        pcre-dev \
        icu-dev \
        icu-data-full \
        libzip-dev \
        zlib-dev \
        git \
        go
    # Утилита "protoc" и официальный плагин генерации клиентского кода "grpc_php_plugin"
RUN mkdir /build && cd /build \
    && git clone --recursive -b v1.72.x https://github.com/grpc/grpc \
    && mkdir -p /build/grpc/cmake/build && cd /build/grpc/cmake/build \
    && cmake ../.. \
    && make protoc grpc_php_plugin \
    # Плагин генерации серверного кода "protoc-gen-php-grpc" от roadrunner
    && cd /build \
    && composer create-project --ignore-platform-reqs spiral/roadrunner-cli \
    && chmod +x ./roadrunner-cli/bin/rr \
    && ./roadrunner-cli/bin/rr download-protoc-binary -l /usr/bin \
    && cp /build/grpc/cmake/build/grpc_php_plugin /usr/bin \
    && cp /build/grpc/cmake/build/third_party/protobuf/protoc /usr/bin \
    && chmod +x /usr/bin/protoc-gen-php-grpc \
    && chmod +x /usr/bin/grpc_php_plugin \
    && chmod +x /usr/bin/protoc \
    # Очистка
    && rm -rf /build

RUN pecl install grpc
RUN docker-php-ext-enable grpc
#RUN docker-php-ext-install tokenizer

WORKDIR /var/www/html/
COPY ./ /var/www/html/
RUN composer install --optimize-autoloader --no-interaction --no-scripts --ignore-platform-req=ext-tokenizer --ignore-platform-req=ext-sockets --ignore-platform-req=ext-grpc --ignore-platform-req=ext-dom --ignore-platform-req=ext-xml --ignore-platform-req=ext-xmlwriter --ignore-platform-req=ext-simplexml
RUN composer download

RUN apk del --purge .build-deps && \
    && pecl clear-cache
