<?php

namespace Logeecom\Tests\BusinessLogic\IntegrationRegistration\DTO;

use Packlink\BusinessLogic\IntegrationRegistration\DTO\IntegrationRegistrationPayload;
use PHPUnit_Framework_TestCase;

/**
 * Class IntegrationRegistrationPayloadTest.
 *
 * @package Logeecom\Tests\BusinessLogic\IntegrationRegistration\DTO
 */
class IntegrationRegistrationPayloadTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorAndGetters()
    {
        $payload = new IntegrationRegistrationPayload(
            'woocommerce',
            'guid-123',
            'My Shop',
            'X-Packlink-Webhook-Secret',
            'secret-abc',
            'https://shop.test/webhook'
        );

        $this->assertEquals('woocommerce', $payload->getIntegrationType());
        $this->assertEquals('guid-123', $payload->getGuid());
        $this->assertEquals('My Shop', $payload->getName());
        $this->assertEquals('X-Packlink-Webhook-Secret', $payload->getWebhookHeaderName());
        $this->assertEquals('secret-abc', $payload->getWebhookHeaderValue());
        $this->assertEquals('https://shop.test/webhook', $payload->getStatusUpdateUrl());
    }

    public function testToArray()
    {
        $payload = new IntegrationRegistrationPayload(
            'prestashop',
            'guid-456',
            'Test Store',
            'X-Packlink-Webhook-Secret',
            'secret-xyz',
            'https://store.test/hook'
        );

        $expected = array(
            'integration_type' => 'prestashop',
            'integration' => array(
                'guid' => 'guid-456',
                'name' => 'Test Store',
            ),
            'webhooks' => array(
                'http_header_name' => 'X-Packlink-Webhook-Secret',
                'http_header_value' => 'secret-xyz',
                'status_update_url' => 'https://store.test/hook',
            ),
        );

        $this->assertEquals($expected, $payload->toArray());
    }

    public function testFromArray()
    {
        $data = array(
            'integration_type' => 'magento',
            'integration' => array(
                'guid' => 'guid-789',
                'name' => 'Magento Shop',
            ),
            'webhooks' => array(
                'http_header_name' => 'X-Packlink-Webhook-Secret',
                'http_header_value' => 'secret-def',
                'status_update_url' => 'https://magento.test/webhook',
            ),
        );

        $payload = IntegrationRegistrationPayload::fromArray($data);

        $this->assertEquals('magento', $payload->getIntegrationType());
        $this->assertEquals('guid-789', $payload->getGuid());
        $this->assertEquals('Magento Shop', $payload->getName());
        $this->assertEquals('X-Packlink-Webhook-Secret', $payload->getWebhookHeaderName());
        $this->assertEquals('secret-def', $payload->getWebhookHeaderValue());
        $this->assertEquals('https://magento.test/webhook', $payload->getStatusUpdateUrl());
    }

    public function testFromArrayRoundTrip()
    {
        $original = array(
            'integration_type' => 'shopify',
            'integration' => array(
                'guid' => 'guid-round',
                'name' => 'Round Trip Shop',
            ),
            'webhooks' => array(
                'http_header_name' => 'X-Packlink-Webhook-Secret',
                'http_header_value' => 'secret-round',
                'status_update_url' => 'https://shopify.test/hook',
            ),
        );

        $payload = IntegrationRegistrationPayload::fromArray($original);

        $this->assertEquals($original, $payload->toArray());
    }

    public function testFromArrayWithMissingKeysUsesDefaults()
    {
        $payload = IntegrationRegistrationPayload::fromArray(array());

        $this->assertEquals('', $payload->getIntegrationType());
        $this->assertEquals('', $payload->getGuid());
        $this->assertEquals('', $payload->getName());
        $this->assertEquals('', $payload->getWebhookHeaderName());
        $this->assertEquals('', $payload->getWebhookHeaderValue());
        $this->assertEquals('', $payload->getStatusUpdateUrl());
    }
}
