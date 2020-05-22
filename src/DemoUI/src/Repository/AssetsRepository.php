<?php

namespace Packlink\DemoUI\Repository;

/**
 * Class AssetsRepository
 * @package Packlink\DemoUI\Repository
 */
class AssetsRepository
{
    /**
     * @param $filePath
     *
     * @return string
     */
    public function getUrl($filePath)
    {
        return $_SERVER['HTTP_HOST'] . '/assets/' . $filePath;
    }
}