FROM nginx:latest

COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

RUN openssl genrsa 2048 > /etc/nginx/localhost.key && \
    openssl req -new -key /etc/nginx/localhost.key -out /etc/nginx/localhost.csr -subj "/C=LO/ST=localhost/L=localhost/O=localhost/OU=localhost/CN=localhost" && \
    openssl x509 -req -days 365 -in /etc/nginx/localhost.csr -signkey /etc/nginx/localhost.key -out /etc/nginx/localhost.crt

WORKDIR /etc/nginx

CMD ["nginx", "-g", "daemon off;"]

EXPOSE 80 443
