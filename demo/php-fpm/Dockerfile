FROM ubuntu:24.04

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install --no-install-recommends -y gnupg curl ca-certificates \
    && mkdir -p /etc/apt/keyrings \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install --no-install-recommends -y \
        php8.5-fpm \
        php8.5-cli \
        php8.5-mysql \
        php8.5-mbstring \
    && apt-get purge -y --auto-remove gnupg \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN sed -i 's/error_log = .*/error_log = \/proc\/self\/fd\/2/' /etc/php/8.5/fpm/php-fpm.conf \
    && sed -i 's/;catch_workers_output = yes/catch_workers_output = yes/g' /etc/php/8.5/fpm/pool.d/www.conf \
    && sed -i 's/;decorate_workers_output = no/decorate_workers_output = no/g' /etc/php/8.5/fpm/pool.d/www.conf \
    && sed -i 's/;php_admin_value\[error_log\] = .*/php_admin_value[error_log] = \/proc\/self\/fd\/2/' /etc/php/8.5/fpm/pool.d/www.conf \
    && sed -i 's/;php_admin_flag\[log_errors\] = .*/php_admin_flag[log_errors] = on/' /etc/php/8.5/fpm/pool.d/www.conf

STOPSIGNAL SIGQUIT

WORKDIR /var/www/html

CMD ["/usr/sbin/php-fpm8.5", "--nodaemonize"]