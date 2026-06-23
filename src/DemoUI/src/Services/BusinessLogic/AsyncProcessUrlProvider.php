<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessUrlProviderInterface;
use Packlink\DemoUI\Services\Integration\UrlService;

/**
 * Class AsyncProcessUrlProvider.
 *
 * @package Packlink\DemoUI\Services\BusinessLogic
 */
class AsyncProcessUrlProvider implements AsyncProcessUrlProviderInterface
{
    /**
     * Returns the async process URL for the given guid.
     *
     * @param string $guid
     *
     * @return string
     */
    public function getAsyncProcessUrl($guid)
    {
        return UrlService::getEndpointUrl('AsyncProcess', 'run') . '&guid=' . $guid;
    }
}
