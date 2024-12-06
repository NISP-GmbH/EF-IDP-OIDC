#!/bin/bash

sudo mkdir -p /etc/ssl/certs/##OIDCAPACHEDOMAIN##
sudo openssl req -x509 -nodes -days 10950 -newkey rsa:2048 \
-keyout /etc/ssl/certs/##OIDCAPACHEDOMAIN##/privkey.pem \
-out /etc/ssl/certs/##OIDCAPACHEDOMAIN##/fullchain.pem \
-subj "/C=US/ST=State/L=City/O=Organization/OU=OrganizationalUnit/CN=##OIDCAPACHEDOMAIN##"

chmod 600 /etc/ssl/certs/##OIDCAPACHEDOMAIN##/privkey.pem
chown -R apache:apache /etc/ssl/certs/##OIDCAPACHEDOMAIN##/
