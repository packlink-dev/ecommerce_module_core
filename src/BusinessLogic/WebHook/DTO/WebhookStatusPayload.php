<?php

namespace Packlink\BusinessLogic\WebHook\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class WebhookStatusPayload.
 *
 * Represents the incoming webhook payload for integration status changes.
 *
 * @package Packlink\BusinessLogic\WebHook\DTO
 */
class WebhookStatusPayload extends DataTransferObject
{
    /**
     * @var string
     */
    private $integrationId;

    /**
     * @var string
     */
    private $status;

    /**
     * WebhookStatusPayload constructor.
     *
     * @param string $integrationId
     * @param string $status
     */
    public function __construct($integrationId, $status)
    {
        $this->integrationId = $integrationId;
        $this->status = $status;
    }

    /**
     * Creates instance from an array.
     *
     * @param array $data Raw data with 'integration_id' and 'status' keys.
     *
     * @return WebhookStatusPayload
     */
    public static function fromArray(array $data)
    {
        return new self(
            static::getDataValue($data, 'integration_id'),
            static::getDataValue($data, 'status')
        );
    }

    /**
     * Transforms DTO to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'integration_id' => $this->integrationId,
            'status' => $this->status,
        );
    }

    /**
     * Returns the integration ID.
     *
     * @return string
     */
    public function getIntegrationId()
    {
        return $this->integrationId;
    }

    /**
     * Returns the status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Validates that the payload has all required fields.
     *
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->integrationId) && !empty($this->status);
    }
}
