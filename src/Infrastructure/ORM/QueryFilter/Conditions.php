<?php

namespace Logeecom\Infrastructure\ORM\QueryFilter;

/**
 * Class Conditions
 * @package Logeecom\Infrastructure\ORM\QueryFilter
 */
final class Conditions
{
    public static $AVAILABLE_OPERATORS = array('=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL');
    public static $TYPE_OPERATORS = array(
        'integer' => array('=', '!=', '>', '>=', '<', '<='),
        'double' => array('=', '!=', '>', '>=', '<', '<='),
        'dateTime' => array('=', '!=', '>', '>=', '<', '<='),
        'string' => array('=', '!=', '>', '>=', '<', '<=', 'LIKE'),
        'array' => array('IN', 'NOT IN'),
        'boolean' => array('=', '!='),
        'NULL' => array('IS NULL', 'IS NOT NULL'),
    );
}