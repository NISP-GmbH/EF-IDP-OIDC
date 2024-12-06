#!/bin/bash

sudo mkdir -p /var/www/html/sso
sudo cp -f code/php/index.php /var/www/html/
sudo cp -f code/php/{logout.php,error.php,callback.php,secure_page.php} /var/www/html/sso/
current_dir=$(pwd)
cd /var/www/html/
sudo composer require jumbojett/openid-connect-php --no-interaction
cd $current_dir
sudo chown -R apache:apache /var/www/html
sudo cp -f code/apache/idp.conf /etc/httpd/conf.d/
sudo systemctl restart httpd
