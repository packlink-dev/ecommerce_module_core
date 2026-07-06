#!/usr/bin/env bash

echo -e "\e[32mPHP 7.0\e[39m"
/usr/bin/php7.0 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.1\e[39m"
/usr/bin/php7.1 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.2\e[39m"
/usr/bin/php7.2 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.3\e[39m"
/usr/bin/php7.3 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 7.4\e[39m"
/usr/bin/php7.4 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 8.0\e[39m"
/usr/bin/php8.0 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 8.1\e[39m"
/usr/bin/php8.1 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 8.2\e[39m"
/usr/bin/php8.2 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 8.3\e[39m"
/usr/bin/php8.3 ./vendor/bin/phpunit --configuration ./phpunit.xml

echo -e "\e[32mPHP 8.4\e[39m"
/usr/bin/php8.4 ./vendor/bin/phpunit --configuration ./phpunit.xml
