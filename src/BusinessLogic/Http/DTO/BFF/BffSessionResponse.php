<?php

namespace Packlink\BusinessLogic\Http\DTO\BFF;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class BffSessionResponse.
 *
 * @package Packlink\BusinessLogic\Http\DTO\BFF
 */
class BffSessionResponse extends DataTransferObject
{
    /**
     * Session identifier.
     *
     * @var string
     */
    public $sessionId;
    /**
     * Tenant name.
     *
     * @var string
     */
    public $tenantName;
    /**
     * Platform identifier.
     *
     * @var string
     */
    public $platform;
    /**
     * Platform country.
     *
     * @var string
     */
    public $platformCountry;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'sessionId' => $this->sessionId,
            'tenantName' => $this->tenantName,
            'platform' => $this->platform,
            'platformCountry' => $this->platformCountry,
        );
    }

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $response = new static();
        $response->sessionId = static::getDataValue($raw, 'sessionId');
        $response->tenantName = static::getDataValue($raw, 'tenantName');
        $response->platform = static::getDataValue($raw, 'platform');
        $response->platformCountry = static::getDataValue($raw, 'platformCountry');

        return $response;
    }
}
