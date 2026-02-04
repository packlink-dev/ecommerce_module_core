<?php

namespace Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution;

use Logeecom\Infrastructure\TaskExecution\AsyncProcessUrlProviderInterface;

class TestAsyncProcessUrlProvider implements AsyncProcessUrlProviderInterface
{
    /**
     * Returns a fake async process URL for testing purposes.
     *
     * @param string $guid
     *
     * @return string
     */
    public function getAsyncProcessUrl($guid)
    {
        return 'http://test/async?guid=' . $guid;
    }
}