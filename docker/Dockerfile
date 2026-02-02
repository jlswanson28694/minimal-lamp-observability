FROM ubuntu:24.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano wget apache2 \
    && mkdir -p /etc/apt/keyrings \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
        libgd3 \
        php8.5-cli \
        php8.5-dev \
        php8.5-fpm \
        # php8.5-sqlite3 \
        php8.5-gd \
        php8.5-curl \
        php8.5-mysql \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-intl \
        php8.5-readline \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -LO https://github.com/prometheus/prometheus/releases/download/v3.9.1/prometheus-3.9.1.linux-amd64.tar.gz \
    && tar xvf prometheus-3.9.1.linux-amd64.tar.gz \
    && cp prometheus-3.9.1.linux-amd64/prometheus prometheus-3.9.1.linux-amd64/promtool /usr/local/bin/ \
    && mkdir /etc/prometheus \
    && mkdir /var/lib/prometheus \
    && curl -LO https://github.com/open-telemetry/opentelemetry-collector-releases/releases/download/v0.143.0/otelcol-contrib_0.143.0_linux_amd64.deb \
    && dpkg -i otelcol-contrib_0.143.0_linux_amd64.deb \
    # && wget -q -O - https://apt.grafana.com/gpg.key | gpg --dearmor | tee /etc/apt/keyrings/grafana.gpg > /dev/null \
    # && echo "deb [signed-by=/etc/apt/keyrings/grafana.gpg] https://apt.grafana.com stable main" | tee -a /etc/apt/sources.list.d/grafana.list \
    # && apt-get update \
    # && apt-get install -y grafana \
    # && /bin/systemctl daemon-reload \
    # && /bin/systemctl enable grafana-server \
    # && /bin/systemctl start grafana-server \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN a2dismod mpm_prefork \
    && a2enmod mpm_event proxy proxy_fcgi actions rewrite headers setenvif status \
    && a2enconf php8.5-fpm \
    && touch /var/log/php8.5-fpm.log \
    && chown www-data:www-data /var/log/php8.5-fpm.log \
    && echo "Listen 8080" >> /etc/apache2/ports.conf \
    && curl -LO https://github.com/hipages/php-fpm_exporter/releases/download/v2.2.0/php-fpm_exporter_2.2.0_linux_amd64.tar.gz \
    && mkdir php-fpm_exporter && tar -xzvf php-fpm_exporter_2.2.0_linux_amd64.tar.gz -C ./php-fpm_exporter && rm php-fpm_exporter_2.2.0_linux_amd64.tar.gz \
    && mv ./php-fpm_exporter/php-fpm_exporter /usr/local/bin && rm -rf ./php-fpm_exporter \
    && wget https://github.com/prometheus/node_exporter/releases/download/v1.10.2/node_exporter-1.10.2.linux-amd64.tar.gz \
    && tar xvfz node_exporter-1.10.2.linux-amd64.tar.gz && rm node_exporter-1.10.2.linux-amd64.tar.gz \
    && mv node_exporter-1.10.2.linux-amd64/node_exporter /usr/local/bin && rm -rf node_exporter-1.10.2.linux-amd64

CMD ["/usr/bin/supervisord", "-n"]