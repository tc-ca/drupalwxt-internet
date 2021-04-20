FROM php:7.2.31-apache

RUN apt-get update && apt-get install --no-install-recommends -y \
        libfreetype6-dev \
        libjpeg-dev \
        libpng-dev \
        libpq-dev \
        git \
        nano \
        vim \
        openssh-server \
        libzip-dev \
        zip \
    && docker-php-ext-configure \
        gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install -j$(nproc) \
        gd \
        opcache \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod \
        rewrite \
    && echo "root:Docker!" | chpasswd

COPY ./docker/apache2/sites-available/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/apache2/ports.conf /etc/apache2/
COPY ./docker/php/php.ini /usr/local/etc/php/
COPY ./docker/ssh/sshd_config /etc/ssh/
COPY ./docker/startup/init.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/init.sh

COPY ./tcwww /var/www

COPY ./docker/fix-permissions.sh /usr/local/fix-permissions.sh
RUN bash /usr/local/fix-permissions.sh --drupal_path=/var/www/html --drupal_user=root

EXPOSE 80 2222

ENV PATH /var/www/vendor/drush/drush:${PATH}
ENV PATH ${PATH}:/home/site/wwwroot

#apache
ENV APACHE_LOG_DIR "/home/LogFiles/apache2"

#drupal storage
ENV DRUPAL_STORAGE_DIR "/home/site/wwwroot"

ENTRYPOINT ["init.sh"]