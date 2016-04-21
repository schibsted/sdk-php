FROM php:5.6-apache

RUN apt-get update -y && apt-get install -y git-core && apt-get install -y zip unzip
COPY docker/default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/bin/composer