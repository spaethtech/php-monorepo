ARG PHP_VERSION
ARG XDEBUG_VERSION

FROM php:${PHP_VERSION:-8.2.4}

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/Chicago

# Copy the shell skeleton to root's home
RUN cp -r /etc/skel/. ~/

# Install & Configure Xdebug
ENV XDEBUG_VERSION=${XDEBUG_VERSION:-3.2.1}
RUN pecl install xdebug-$XDEBUG_VERSION \
 && docker-php-ext-enable xdebug
ADD xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# TODO: Install any additional PHP modules here...

# Install Composer
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install desired packages
RUN apt-get update \
 && apt-get install -y nano iproute2

# TODO: Install any additional software here...

# Clean up from package installations
RUN apt-get clean
