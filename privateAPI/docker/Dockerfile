FROM php:8.2.12-fpm


RUN apt-get update && apt-get install -y \
        libssh2-1-dev libssh2-1 \
        libfreetype6-dev libjpeg62-turbo-dev libwebp-dev libgmp-dev libmagickwand-dev libpng-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        wget curl \
        zip unzip git \
        make gcc pkg-config autoconf g++ yasm cmake \
        libde265-0 libde265-dev x265 libx265-dev libtool libpng16-16 libpng-dev libwebp-dev libgomp1 libwebpmux3 libwebpdemux2 \
        ghostscript \
        libxml2-dev \
        libxml2-utils \
            --no-install-recommends


RUN docker-php-ext-install bcmath zip intl mbstring pdo_mysql mysqli opcache exif pcntl sockets ffi \
    && cd ${SRC} && git clone https://github.com/igbinary/igbinary "php-igbinary" \
    && cd php-igbinary \
    && phpize && ./configure \
    && make && make install && make clean \
    && docker-php-ext-enable igbinary \
    && cd ${SRC} && git clone -b 5.3.7 https://github.com/phpredis/phpredis "php-redis" \
    && cd php-redis \
    && phpize && ./configure --enable-redis-igbinary \
    && make && make install && make clean \
    && docker-php-ext-enable redis \
    && docker-php-ext-install zip


RUN pecl install grpc
RUN pecl install protobuf
RUN pecl install opentelemetry


COPY ./install-composer.sh /
COPY ./php.ini /usr/local/etc/php/
COPY ./www.conf /usr/local/etc/php/


RUN apt-get purge -y g++ \
    && apt-get autoremove -y \
    && rm -r /var/lib/apt/lists/* \
    && rm -rf /tmp/* \
    && sh /install-composer.sh \
    && rm /install-composer.sh

RUN usermod -u 1000 www-data

VOLUME /root/.composer

EXPOSE 9000

CMD ["php-fpm"]
