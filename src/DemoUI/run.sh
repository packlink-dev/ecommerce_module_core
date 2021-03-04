#!/usr/bin/env bash

composer install

PL_PLATFORM="$1"

if [ "$PL_PLATFORM" = "" ]; then
  PL_PLATFORM = "PRO"
fi

export PL_PLATFORM

xdg-open http://localhost:7000/Views/$PL_PLATFORM/index.php

cd $PWD/src && php -S localhost:7000