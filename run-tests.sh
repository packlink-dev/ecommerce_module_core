#!/usr/bin/env bash

echo -e "\e[32mPHP 5.6\e[39m"
/usr/bin/php5.6 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.0\e[39m"
/usr/bin/php7.0 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.1\e[39m"
/usr/bin/php7.1 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.2\e[39m"
/usr/bin/php7.2 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.3\e[39m"
/usr/bin/php7.3 ./vendor/bin/phpunit --configuration ./phpunit.xml
