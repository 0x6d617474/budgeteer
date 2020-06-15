ARG TARGET_PHP_VERSION=7.4

FROM php:${TARGET_PHP_VERSION}-fpm

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin
RUN install-php-extensions ldap pdo_mysql zip

ARG LUID=1000
ENV LUID ${LUID}
ARG LGID=1000
ENV LGID ${LGID}

RUN groupadd -g ${LGID} developer && \
    useradd -u ${LUID} -g developer -m developer && \
    usermod -p "*" developer -s /bin/bash

USER developer

WORKDIR "/var/www"

CMD ["php-fpm"]

EXPOSE 9000
