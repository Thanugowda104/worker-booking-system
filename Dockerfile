FROM php:8.2-cli

# Install MySQL PDO extension (IMPORTANT FIX)
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app

COPY . .

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
