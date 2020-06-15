FROM phusion/baseimage:bionic-1.0.0

RUN DEBIAN_FRONTEND=noninteractive
RUN locale-gen en_US.UTF-8

ENV LANGUAGE=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8
ENV LC_CTYPE=en_US.UTF-8
ENV LANG=en_US.UTF-8
ENV TERM=xterm

ARG TZ=UTC
ENV TZ ${TZ}

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get install -y software-properties-common && \
    add-apt-repository -y ppa:ondrej/php

RUN echo 'DPkg::options { "--force-confdef"; };' >> /etc/apt/apt.conf

ARG TARGET_PHP_VERSION=7.4
ARG TARGET_NODE_VERSION=14

RUN apt-get update && \
    apt-get upgrade -y && \
    curl -sL https://deb.nodesource.com/setup_${TARGET_NODE_VERSION}.x | bash - && \
    apt-get install -y --allow-downgrades --allow-remove-essential \
        --allow-change-held-packages --no-install-recommends \
        php${TARGET_PHP_VERSION}-cli \
        php${TARGET_PHP_VERSION}-common \
        php${TARGET_PHP_VERSION}-curl \
        php${TARGET_PHP_VERSION}-intl \
        php${TARGET_PHP_VERSION}-json \
        php${TARGET_PHP_VERSION}-ldap \
        php${TARGET_PHP_VERSION}-xml \
        php${TARGET_PHP_VERSION}-mbstring \
        php${TARGET_PHP_VERSION}-mysql \
        php${TARGET_PHP_VERSION}-pgsql \
        php${TARGET_PHP_VERSION}-sqlite \
        php${TARGET_PHP_VERSION}-sqlite3 \
        php${TARGET_PHP_VERSION}-zip \
        php${TARGET_PHP_VERSION}-bcmath \
        php${TARGET_PHP_VERSION}-memcached \
        php${TARGET_PHP_VERSION}-gd \
        php${TARGET_PHP_VERSION}-dev \
        nodejs \
        nasm \
        pkg-config \
        libcurl4-openssl-dev \
        libedit-dev \
        libldap2-dev \
        libssl-dev \
        libzip-dev zip unzip \
        libxml2-dev \
        xz-utils \
        libsqlite3-dev \
        sqlite3 \
        git \
        curl \
        vim \
        nano \
        bash-completion \
        postgresql-client \
        mariadb-client \
        ssh \
        gnupg2 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN curl -s http://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

RUN npm install -g npm npm-check-updates

ARG LUID=1000
ENV LUID ${LUID}
ARG LGID=1000
ENV LGID ${LGID}

RUN groupadd -g ${LGID} developer && \
    useradd -u ${LUID} -g developer -m developer -G docker_env && \
    usermod -p "*" developer -s /bin/bash

USER developer

WORKDIR "/var/www"
