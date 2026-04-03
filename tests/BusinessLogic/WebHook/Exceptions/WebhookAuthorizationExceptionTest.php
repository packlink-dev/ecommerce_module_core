<?php

namespace Logeecom\Tests\BusinessLogic\WebHook\Exceptions;

use Packlink\BusinessLogic\WebHook\Exceptions\WebhookAuthorizationException;
use PHPUnit\Framework\TestCase;

/**
 * Class WebhookAuthorizationExceptionTest.
 *
 * @package Logeecom\Tests\BusinessLogic\WebHook\Exceptions
 */
class WebhookAuthorizationExceptionTest extends TestCase
{
    /**
     * Tests that the exception can be instantiated with a message.
     */
    public function testExceptionMessage()
    {
        $exception = new WebhookAuthorizationException('Unauthorized.');

        $this->assertEquals('Unauthorized.', $exception->getMessage());
    }

    /**
     * Tests that the exception extends BaseException.
     */
    public function testExtendsBaseException()
    {
        $exception = new WebhookAuthorizationException('test');

        $this->assertInstanceOf(
            'Logeecom\Infrastructure\Exceptions\BaseException',
            $exception
        );
    }
}
