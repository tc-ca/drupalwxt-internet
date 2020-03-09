FROM php:7.3.0-apache

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        #For redirects
        libaprutil1-dbd-pgsql \
        git \
        --no-install-recommends openssh-server vim \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install opcache \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite \
    && a2enmod proxy \
    && a2enmod proxy_http \
    && a2enmod ssl \
    #For redirects
    && a2enmod dbd \
    && echo "root:Docker!" | chpasswd

COPY ./docker/apache2/sites-available/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/ssh/sshd_config /etc/ssh/
COPY ./docker/startup/init.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/init.sh

COPY ./tcwww /var/www

COPY ./docker/fix-permissions.sh /usr/local/fix-permissions.sh
RUN bash /usr/local/fix-permissions.sh --drupal_path=/var/www/html --drupal_user=root

EXPOSE 80 2222

ENV PATH /var/www/vendor/drush/drush:${PATH}
ENV PATH ${PATH}:/home/site/wwwroot

ENTRYPOINT ["init.sh"]