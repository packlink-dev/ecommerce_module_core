#!/usr/bin/env bash
#
# Runs the test suite against every supported PHP interpreter (7.0 - 8.4).
#
# composer.lock is resolved for PHP >= 8.2 (phpunit 11). Older interpreters
# need a compatible phpunit ("^4.8 || ^9.6 || ^11.0" in composer.json), so
# this script re-resolves the phpunit requirement with each interpreter before
# running it. Any nonzero composer/phpunit exit fails the whole run - a partial
# run must never look green. Interpreters that are not installed are reported
# and skipped (the run is marked incomplete rather than passing).

set -u

COMPOSER="$(command -v composer)"
STATUS=0
SKIPPED=""

run_for() {
    label="$1"
    php_bin="$2"

    if [ ! -x "$php_bin" ]; then
        echo -e "\e[33m${label}: ${php_bin} not found - SKIPPED\e[39m"
        SKIPPED="${SKIPPED} ${label}"
        return
    fi

    echo -e "\e[32m${label}\e[39m"

    if ! "$php_bin" "$COMPOSER" update phpunit/phpunit --with-all-dependencies --no-scripts --quiet; then
        echo -e "\e[31m${label}: composer could not resolve phpunit\e[39m"
        STATUS=1
        return
    fi

    if ! "$php_bin" ./vendor/bin/phpunit --configuration ./phpunit.xml; then
        echo -e "\e[31m${label}: tests failed\e[39m"
        STATUS=1
    fi
}

run_for "PHP 7.0" /usr/bin/php7.0
run_for "PHP 7.1" /usr/bin/php7.1
run_for "PHP 7.2" /usr/bin/php7.2
run_for "PHP 7.3" /usr/bin/php7.3
run_for "PHP 7.4" /usr/bin/php7.4
run_for "PHP 8.0" /usr/bin/php8.0
run_for "PHP 8.1" /usr/bin/php8.1
run_for "PHP 8.2" /usr/bin/php8.2
run_for "PHP 8.3" /usr/bin/php8.3
run_for "PHP 8.4" /usr/bin/php8.4

if [ -n "$SKIPPED" ]; then
    echo -e "\e[33mIncomplete run - not verified on:${SKIPPED}\e[39m"
    STATUS=1
fi

if [ "$STATUS" -eq 0 ]; then
    echo -e "\e[32mAll interpreters passed.\e[39m"
fi

exit "$STATUS"
