<?php

namespace Packlink\BusinessLogic\Http\DTO;

/**
 * Class ShippingService hold primary details about shipping service.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class ShippingService extends BaseDto
{
    /**
     * Service Id.
     *
     * @var int
     */
    public $id;
    /**
     * Indicates whether service is enabled.
     *
     * @var bool
     */
    public $enabled;
    /**
     * Carrier name.
     *
     * @var string
     */
    public $carrierName;
    /**
     * Service name.
     *
     * @var string
     */
    public $serviceName;
    /**
     * Public URL to the service logo.
     *
     * @var string
     */
    public $logoUrl;
    /**
     * Indicates whether service requires departure drop-off.
     *
     * @var bool
     */
    public $departureDropOff;
    /**
     * Indicates whether service requires destination drop-off.
     *
     * @var bool
     */
    public $destinationDropOff;
    /**
     * Details about service. Array of strings.
     *
     * @var array
     */
    public $serviceDetails;
    /**
     * Packlink details about service. Array of strings.
     *
     * @var array
     */
    public $packlinkInfo;

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return array(
            'service_id' => $this->id,
            'enabled' => $this->enabled,
            'carrier_name' => $this->carrierName,
            'service_name' => $this->serviceName,
            'service_logo' => $this->logoUrl,
            'departure_type' => $this->departureDropOff ? 'drop-off' : 'pick-up',
            'destination_type' => $this->destinationDropOff ? 'drop-off' : 'home',
            'service_details' => $this->serviceDetails,
            'packlink_info' => $this->packlinkInfo,
        );
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();

        $instance->id = (int)self::getValue($raw, 'service_id');
        $instance->enabled = (bool)self::getValue($raw, 'enabled');
        $instance->carrierName = self::getValue($raw, 'carrier_name');
        $instance->serviceName = self::getValue($raw, 'service_name');
        $instance->logoUrl = self::getValue($raw, 'service_logo');
        $instance->departureDropOff = self::getValue($raw, 'departure_type') === 'drop-off';
        $instance->destinationDropOff = self::getValue($raw, 'destination_type') === 'drop-off';
        $instance->serviceDetails = self::getValue($raw, 'service_details', array());
        $instance->packlinkInfo = self::getValue($raw, 'packlink_info', array());

        return $instance;
    }
}
