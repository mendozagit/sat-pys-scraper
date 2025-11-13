FROM php:8.4-cli-alpine

COPY . /opt/sat-pys-scraper
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# install dependencies for php modules
RUN set -e \
    && apk add git libzip-dev \
    && docker-php-ext-install zip \
    && apk del libzip-dev

# set up php
RUN set -e \
    && mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && sed -i 's/^variables_order.*/variables_order=EGPCS/' /usr/local/etc/php/php.ini \
    && php -i

# build project
RUN set -e \
    && rm -r -f /opt/sat-pys-scraper/composer.lock /opt/sat-pys-scraper/vendor \
    && composer update --working-dir=/opt/sat-pys-scraper --no-dev --prefer-dist --optimize-autoloader --no-interaction \
    && rm -rf "$(composer config cache-dir --global)" "$(composer config data-dir --global)" "$(composer config home --global)"

ENV TZ="America/Mexico_City"

ENTRYPOINT ["/usr/local/bin/php", "/opt/sat-pys-scraper/bin/sat-pys-scraper"]
