FROM php:5.6-apache

RUN apt-get update -y && apt-get install -y git-core && apt-get install -y zip unzip

RUN curl -sS https://getcomposer.org/installer | php