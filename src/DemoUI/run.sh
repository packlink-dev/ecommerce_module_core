#!/usr/bin/env bash

composer install

xdg-open http://localhost:7000/Views/index.php

cd $PWD/src && php -S localhost:7000