#!/bin/bash

[[ ! -e /.dockerenv ]] && exit 0

set -xe

# copy php-ci.ini to PHP_INI_SCAN_DIR
cp /builds/php-platform/restful/ci/php-ci.ini /usr/local/etc/php/conf.d/php-ci.ini

# apache configuration
echo "<Directory /var/www/html/restful>" > /etc/apache2/conf-available/restful.conf
echo "    AllowOverride All" >> /etc/apache2/conf-available/restful.conf
echo "</Directory>" >> /etc/apache2/conf-available/restful.conf
a2enconf restful.conf
a2ensite 000-default
a2enmod rewrite
apache2ctl start

# move softlink build directory to directory acccesible from apache
ln -s /builds/php-platform/restful /var/www/html/restful

# composer update
composer update
