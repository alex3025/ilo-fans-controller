FROM php:apache

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions ssh2

COPY favicon.ico /var/www/html/
COPY ilo-fans-controller.php /var/www/html/index.php

COPY config.inc.php.env /var/www/html/config.inc.php
