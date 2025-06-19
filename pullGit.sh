#!/bin/sh

rm -rf /usr/share/nginx/html/*

if [ -d "/tmp/repo" ]; then
    cd /tmp/repo
    git pull origin main
else
    git clone https://github.com/axlmrie/Vide-Grenier.git /tmp/repo
    cd /tmp/repo
    git checkout main
fi

cp -r /tmp/repo/* /usr/share/nginx/html/

chmod -R a+r /usr/share/nginx/html/
chown -R nginx:nginx /usr/share/nginx/html/

exec "$@"
