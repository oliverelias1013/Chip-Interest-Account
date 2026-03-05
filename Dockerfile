FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .

CMD ["vendor/bin/phpunit"]