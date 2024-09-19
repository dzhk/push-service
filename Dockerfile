FROM php:8.2.12-fpm as base


RUN apt-get update \
    && apt-get install -y --no-install-recommends libz-dev libpq-dev libjpeg-dev libpng-dev libssl-dev libzip-dev unzip zip git g++ zlib1g-dev \
    && apt-get clean \
    && docker-php-ext-configure gd \
    && docker-php-ext-configure zip \
    && docker-php-ext-install gd exif opcache pdo_mysql pcntl zip
RUN  pecl install opentelemetry \
    && docker-php-ext-enable opentelemetry
#    && pecl install grpc \
#    && docker-php-ext-enable grpc
RUN  cd ${SRC} && git clone https://github.com/igbinary/igbinary "php-igbinary" \
    && cd php-igbinary \
    && phpize && ./configure \
    && make && make install && make clean \
    && docker-php-ext-enable igbinary \
    && cd ${SRC} && git clone -b 6.0.2 https://github.com/phpredis/phpredis "php-redis" \
    && cd php-redis \
    && phpize && ./configure --enable-redis-igbinary \
    && make && make install && make clean \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*;
#RUN  pecl install protobuf \
#    && docker-php-ext-enable protobuf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


FROM base as backend

WORKDIR /var/www/srv-push

COPY ./privateAPI/composer.json /var/www/srv-push/privateAPI/

RUN composer install -n --no-dev --no-cache --no-ansi --no-autoloader --no-scripts --prefer-dist -d /var/www/srv-push/privateAPI
COPY --chown=www-data:www-data ./privateAPI/ /var/www/srv-push/privateAPI/
COPY --chown=www-data:www-data ./src/ /var/www/srv-push/src/
RUN composer dump-autoload -n --optimize -d /var/www/srv-push/privateAPI


COPY ./.werf/php.ini /usr/local/etc/php/php.ini
COPY ./.werf/zzz-php-fpm-config.conf /usr/local/etc/php-fpm.d/zzz-php-fpm-config.conf

EXPOSE 9000


FROM base as cli

WORKDIR /var/www/srv-push

COPY ./console/composer.json /var/www/srv-push/console/

RUN composer install -n --no-dev --no-cache --no-ansi --no-autoloader --no-scripts --prefer-dist -d /var/www/srv-push/console
COPY --chown=www-data:www-data ./console/ /var/www/srv-push/console/
COPY --chown=www-data:www-data ./src/ /var/www/srv-push/src/
RUN composer dump-autoload -n --optimize -d /var/www/srv-push/console
WORKDIR /var/www/srv-push/console/app


FROM nginx:1.24 as frontend
WORKDIR /www
COPY --from=backend /var/www/srv-push/privateAPI/public /www
EXPOSE 8080