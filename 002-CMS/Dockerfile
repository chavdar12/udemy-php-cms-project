FROM php:8.5-cli
RUN docker-php-ext-install pdo_mysql
WORKDIR /app
COPY . .
EXPOSE 8000
CMD ["sh", "-c", "php bin/setup.php && php -S 0.0.0.0:8000 -t public public/index.php"]
