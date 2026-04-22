<?php

namespace Packlink\BusinessLogic\IntegrationRegistration\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class IntegrationRegistrationPayload.
 *
 * Represents the request payload for registering an integration with the Packlink API.
 *
 * @package Packlink\BusinessLogic\IntegrationRegistration\DTO
 */
class IntegrationRegistrationPayload extends DataTransferObject
{
    /**
     * @var string
     */
    private $integrationType;

    /**
     * @var string
     */
    private $guid;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $webhookHeaderName;

    /**
     * @var string
     */
    private $webhookHeaderValue;

    /**
     * @var string
     */
    private $statusUpdateUrl;

    /**
     * IntegrationRegistrationPayload constructor.
     *
     * @param string $integrationType
     * @param string $guid
     * @param string $name
     * @param string $webhookHeaderName
     * @param string $webhookHeaderValue
     * @param string $statusUpdateUrl
     */
    public function __construct(
        $integrationType,
        $guid,
        $name,
        $webhookHeaderName,
        $webhookHeaderValue,
        $statusUpdateUrl
    ) {
        $this->integrationType = $integrationType;
        $this->guid = $guid;
        $this->name = $name;
        $this->webhookHeaderName = $webhookHeaderName;
        $this->webhookHeaderValue = $webhookHeaderValue;
        $this->statusUpdateUrl = $statusUpdateUrl;
    }

    /**
     * Creates instance from an array.
     *
     * @param array $data
     *
     * @return IntegrationRegistrationPayload
     */
    public static function fromArray(array $data)
    {
        $integration = static::getDataValue($data, 'integration', array());
        $webhooks = static::getDataValue($data, 'webhooks', array());

        return new self(
            static::getDataValue($data, 'integration_type'),
            static::getDataValue($integration, 'guid'),
            static::getDataValue($integration, 'name'),
            static::getDataValue($webhooks, 'http_header_name'),
            static::getDataValue($webhooks, 'http_header_value'),
            static::getDataValue($webhooks, 'status_update_url')
        );
    }

    /**
     * Transforms DTO to array matching the Packlink API contract.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'integration_type' => $this->integrationType,
            'integration' => array(
                'guid' => $this->guid,
                'name' => $this->name,
            ),
            'webhooks' => array(
                'http_header_name' => $this->webhookHeaderName,
                'http_header_value' => $this->webhookHeaderValue,
                'status_update_url' => $this->statusUpdateUrl,
            ),
        );
    }

    /**
     * @return string
     */
    public function getIntegrationType()
    {
        return $this->integrationType;
    }

    /**
     * @return string
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getWebhookHeaderName()
    {
        return $this->webhookHeaderName;
    }

    /**
     * @return string
     */
    public function getWebhookHeaderValue()
    {
        return $this->webhookHeaderValue;
    }

    /**
     * @return string
     */
    public function getStatusUpdateUrl()
    {
        return $this->statusUpdateUrl;
    }
}
