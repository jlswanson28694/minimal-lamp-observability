FROM ubuntu/apache2:latest

RUN a2dismod mpm_prefork \
    && a2enmod mpm_event proxy proxy_fcgi actions rewrite headers setenvif status