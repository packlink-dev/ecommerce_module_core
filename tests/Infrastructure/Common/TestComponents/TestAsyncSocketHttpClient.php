<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents;

use Logeecom\Infrastructure\Http\AsyncSocketHttpClient;

class TestAsyncSocketHttpClient extends AsyncSocketHttpClient
{
    public $requestHistory = array();

    protected function executeRequest($transferProtocol, $host, $port, $timeOut, $payload)
    {
        $this->requestHistory[] = array(
            'transferProtocol' => $transferProtocol,
            'host' => $host,
            'port' => $port,
            'timeout' => $timeOut,
            'payload' => $payload,
        );
    }
}