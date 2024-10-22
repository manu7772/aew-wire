FROM php:8.3-cli

# # Dependencies
RUN apt-get update --fix-missing \
    && apt-get install -y \
        g++ \
        zip \
        unzip \
        wget \
        curl \
        nano \
        git \
#         libpq-dev \
#         libicu-dev \
#         zlib1g-dev \
#         libzip-dev \
#         libwebp-dev \
#         libjpeg-dev \
#         libfreetype6-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# # PHP Extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    bcmath \
    intl \
    opcache \
    gd \
    pdo \
    pdo_mysql \
    zip

# RUN echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/docker-php-ext-addon.ini
# RUN echo 'date.timezone=Europe/Paris' >> /usr/local/etc/php/conf.d/docker-php-ext-addon.ini
# RUN echo 'post_max_size=20M' >> /usr/local/etc/php/conf.d/docker-php-ext-addon.ini
# RUN echo 'upload_max_filesize=20M' >> /usr/local/etc/php/conf.d/docker-php-ext-addon.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Working directory
WORKDIR /bundle