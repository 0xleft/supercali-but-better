FROM php:7.2-apache

WORKDIR /var/www/html

COPY . /var/www/html

RUN docker-php-ext-install mysqli  
RUN docker-php-ext-enable mysqli

EXPOSE 80