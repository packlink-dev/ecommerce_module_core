<?php

namespace Packlink\DemoUI\Services\Integration;

/**
 * Class UrlService
 * @package Packlink\DemoUI\Repository
 */
class UrlService
{
    /**
     * @var string (http/https)
     */
    private $schema;

    public function __construct()
    {
        $this->schema = empty($_SERVER['HTTPS']) ? 'http' : 'https';
    }

    /**
     * @param $controllerName
     * @param $action
     *
     * @return string
     */
    public function getEndpointUrl($controllerName, $action)
    {
        return "{$this->schema}://{$_SERVER['HTTP_HOST']}/Controllers/Index.php?controller={$controllerName}&action={$action}";
    }

    /**
     * @param $filePath
     *
     * @return string
     */
    public function getAssetsUrl($filePath)
    {
        return "{$this->schema}}://{$_SERVER['HTTP_HOST']}/Controllers/assets/{$filePath}";
    }
}