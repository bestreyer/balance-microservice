FROM php:7-cli-alpine

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS openssl-dev && \
    pecl install ev && \
    echo "extension=ev.so" > /usr/local/etc/php/conf.d/docker-php-ext-ev.ini && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    apk del .build-deps && \
    apk add --update --no-cache git

# cache level for composer
ADD composer.json symfony.lock composer.lock /opt/application/
WORKDIR "/opt/application"

RUN composer install --no-scripts

COPY . /opt/application

RUN cp .env.dist .env && composer install

ENTRYPOINT ["bin/console", "app:worker:start"]