<?php

/*
 *   Copyright (c) 2013 Ben Ramsey <http://benramsey.com>
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a
 *   copy of this software and associated documentation files (the "Software"),
 *   to deal in the Software without restriction, including without limitation
 *   the rights to use, copy, modify, merge, publish, distribute, sublicense,
 *   and/or sell copies of the Software, and to permit persons to whom the
 *   Software is furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *   FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *   DEALINGS IN THE SOFTWARE.
 */

namespace Packlink\BusinessLogic\Utility\Php;

/**
 * Used from symfony/polyfill-php55.
 *
 * @internal
 */
class Php55
{
    /**
     * Return the values from a single column in the input array.
     *
     * @link https://secure.php.net/manual/en/function.array-column.php
     *
     * @param array $input <p>A multi-dimensional array (record set) from which to pull a column of values.</p>
     * @param mixed $columnKey <p>The column of values to return. This value may be the integer key of the
     *  column you wish to retrieve, or it may be the string key name for an associative array.
     *  It may also be NULL to return complete arrays (useful together with index_key to reindex the array).</p>
     * @param mixed $indexKey [optional] <p>The column to use as the index/keys for the returned array.
     *  This value may be the integer key of the column, or it may be the string key name.</p>
     *
     * @return array Returns an array of values representing a single column from the input array.
     */
    public static function arrayColumn(array $input, $columnKey, $indexKey = null)
    {
        if (function_exists('array_column')) {
            return array_column($input, $columnKey, $indexKey);
        }

        return self::getArrayColumn($input, $columnKey, $indexKey);
    }

    /**
     * Return the values from a single column in the input array.
     *
     * @link https://secure.php.net/manual/en/function.array-column.php
     *
     * @param array $input <p>A multi-dimensional array (record set) from which to pull a column of values.</p>
     * @param mixed $columnKey <p>The column of values to return. This value may be the integer key of the
     *  column you wish to retrieve, or it may be the string key name for an associative array.
     *  It may also be NULL to return complete arrays (useful together with index_key to reindex the array).</p>
     * @param mixed $indexKey [optional] <p>The column to use as the index/keys for the returned array.
     *  This value may be the integer key of the column, or it may be the string key name.</p>
     *
     * @return array Returns an array of values representing a single column from the input array.
     */
    private static function getArrayColumn(array $input, $columnKey, $indexKey = null)
    {
        $output = array();

        foreach ($input as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if (null !== $indexKey && \array_key_exists($indexKey, $row)) {
                $keySet = true;
                $key = (string)$row[$indexKey];
            }

            if (null === $columnKey) {
                $valueSet = true;
                $value = $row;
            } elseif (\is_array($row) && \array_key_exists($columnKey, $row)) {
                $valueSet = true;
                $value = $row[$columnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $output[$key] = $value;
                } else {
                    $output[] = $value;
                }
            }
        }

        return $output;
    }
}
