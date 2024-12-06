#!/bin/bash

# Detect OS version
if cat /etc/redhat-release | egrep -qi "(Red Hat.*8\..*|Alma.*Linux.*8\..*|Rocky.*Linux.*8\..*)"
then
    # RHEL 8 steps
    sudo dnf -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
    sudo dnf -y install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
elif cat /etc/redhat-release | egrep -qi "(Red Hat.*9\..*|Alma.*Linux.*9\..*|Rocky.*Linux.*9\..*)"
then
    # RHEL 9 steps
    sudo dnf -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-9.noarch.rpm
    sudo dnf -y install https://rpms.remirepo.net/enterprise/remi-release-9.rpm
else
    echo "Unsupported OS version"
    exit 1
fi

# Common steps for both versions
sudo dnf -y install yum-utils
sudo dnf -y remove php
sudo dnf -y module reset php
sudo dnf -y module install php:remi-8.2
sudo dnf -y install httpd php php-common php-intl mod_ssl mod_proxy_html composer

# Enable and restart Apache
sudo systemctl enable --now httpd
sudo systemctl restart httpd
