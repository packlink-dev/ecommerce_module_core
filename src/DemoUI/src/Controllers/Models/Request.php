<?php

namespace Packlink\DemoUI\Controllers\Models;

/**
 * Class Request
 * @package Packlink\DemoUI\Controllers\Models
 */
class Request
{
    /**
     * @var array
     */
    private $query;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var array
     */
    private $headers;

    /**
     * Request constructor.
     *
     * @param array $query
     * @param array $payload
     * @param array $headers
     */
    public function __construct(array $query, array $payload, array $headers)
    {
        $this->query = $query;
        $this->payload = $payload;
        $this->headers = $headers;
    }

    /**
     * @param string|null $key
     *
     * @return string|null
     */
    public function getQuery($key = null)
    {
        if ($key !== null && isset($this->query[$key])) {
            return $this->query[$key];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param string|null $key
     *
     * @return array
     */
    public function getHeaders($key = null)
    {
        if ($key !== null && isset($this->headers[$key])) {
            return $this->headers[$key];
        }

        return $this->headers;
    }
}