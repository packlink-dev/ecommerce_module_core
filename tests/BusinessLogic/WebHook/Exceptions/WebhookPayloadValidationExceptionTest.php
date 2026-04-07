<?php

namespace Logeecom\Tests\BusinessLogic\WebHook\Exceptions;

use Packlink\BusinessLogic\WebHook\Exceptions\WebhookPayloadValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Class WebhookPayloadValidationExceptionTest.
 *
 * @package Logeecom\Tests\BusinessLogic\WebHook\Exceptions
 */
class WebhookPayloadValidationExceptionTest extends TestCase
{
    /**
     * Tests that the exception can be instantiated with a message.
     */
    public function testExceptionMessage()
    {
        $exception = new WebhookPayloadValidationException('Invalid payload.');

        $this->assertEquals('Invalid payload.', $exception->getMessage());
    }

    /**
     * Tests that the exception extends BaseException.
     */
    public function testExtendsBaseException()
    {
        $exception = new WebhookPayloadValidationException('test');

        $this->assertInstanceOf(
            'Logeecom\Infrastructure\Exceptions\BaseException',
            $exception
        );
    }
}
