#!/bin/sh
printf "${PROM_USERNAME}:$(openssl passwd -apr1 ${PROM_PASSWORD})\n" > /etc/nginx/.htpasswd 2>/dev/null