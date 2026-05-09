FROM php:8.2-apache
RUN apt-get update && apt-get upgrade -y
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli
WORKDIR /var/www/html
COPY src/ /var/www/html/
# COPY pod_studio.conf /etc/apache2/conf-available/default.conf
RUN echo  "ServerName localhost" >> /etc/apache2/apache2.conf
EXPOSE 80
