FROM php:8.2-fpm
ARG user
ARG uid
RUN apt-get update && apt-get install -y git curl libpng-dev libonig-dev libxml2-dev zip unzip
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user
RUN apt-get update \
    && apt-get install -y default-mysql-client \
    && apt-get install -y git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY /wait-for-db.sh /usr/local/bin/wait-for-db.sh
RUN chmod +x /usr/local/bin/wait-for-db.sh
ENTRYPOINT ["wait-for-db.sh"]


WORKDIR /var/www
USER $user

