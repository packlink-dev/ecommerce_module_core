<?php

namespace Logeecom\Tests\BusinessLogic\WebHook\DTO;

use Packlink\BusinessLogic\WebHook\DTO\WebhookStatusPayload;
use PHPUnit\Framework\TestCase;

/**
 * Class WebhookStatusPayloadTest.
 *
 * @package Logeecom\Tests\BusinessLogic\WebHook\DTO
 */
class WebhookStatusPayloadTest extends TestCase
{
    /**
     * Tests that fromArray creates a valid payload.
     */
    public function testFromArray()
    {
        $payload = WebhookStatusPayload::fromArray(array(
            'integration_id' => 'test-id',
            'status' => 'ENABLED',
        ));

        $this->assertEquals('test-id', $payload->getIntegrationId());
        $this->assertEquals('ENABLED', $payload->getStatus());
    }

    /**
     * Tests that toArray returns the expected structure.
     */
    public function testToArray()
    {
        $payload = new WebhookStatusPayload('test-id', 'DISABLED');
        $result = $payload->toArray();

        $this->assertEquals('test-id', $result['integration_id']);
        $this->assertEquals('DISABLED', $result['status']);
    }

    /**
     * Tests that isValid returns true for a complete payload.
     */
    public function testIsValidReturnsTrueForCompletePayload()
    {
        $payload = new WebhookStatusPayload('test-id', 'ENABLED');

        $this->assertTrue($payload->isValid());
    }

    /**
     * Tests that isValid returns false when integration_id is empty.
     */
    public function testIsValidReturnsFalseWhenIntegrationIdEmpty()
    {
        $payload = new WebhookStatusPayload('', 'ENABLED');

        $this->assertFalse($payload->isValid());
    }

    /**
     * Tests that isValid returns false when status is empty.
     */
    public function testIsValidReturnsFalseWhenStatusEmpty()
    {
        $payload = new WebhookStatusPayload('test-id', '');

        $this->assertFalse($payload->isValid());
    }

    /**
     * Tests that fromArray handles missing keys gracefully.
     */
    public function testFromArrayWithMissingKeys()
    {
        $payload = WebhookStatusPayload::fromArray(array());

        $this->assertEquals('', $payload->getIntegrationId());
        $this->assertEquals('', $payload->getStatus());
        $this->assertFalse($payload->isValid());
    }
}
