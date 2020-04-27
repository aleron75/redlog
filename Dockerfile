FROM composer:latest AS builder
COPY . /app
WORKDIR /app
RUN composer install --no-dev
RUN chmod +x ./redlog
RUN rm -Rf .env

FROM php:7.3-cli
COPY --from=builder /app /app
VOLUME /app/.env
WORKDIR /app
ENTRYPOINT [ "php", "./redlog" ]
